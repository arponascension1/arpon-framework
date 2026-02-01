<?php

namespace Arpon\Foundation;

use Closure;
use Exception;
use ReflectionClass;
use ReflectionParameter;

class Container
{
    protected static $instance;
    protected $bindings = [];
    protected $instances = [];
    protected $aliases = [];
    protected $resolved = [];

    public static function getInstance()
    {
        return static::$instance;
    }

    public static function setInstance(Container $container = null)
    {
        return static::$instance = $container;
    }

    public function bound($abstract)
    {
        return isset($this->bindings[$abstract]) || isset($this->instances[$abstract]) || $this->isAlias($abstract);
    }

    public function has($id)
    {
        return $this->bound($id);
    }

    public function isAlias($name)
    {
        return isset($this->aliases[$name]);
    }

    public function bind($abstract, $concrete = null, $shared = false)
    {
        $this->dropStaleInstances($abstract);

        if (is_null($concrete)) {
            $concrete = $abstract;
        }

        $this->bindings[$abstract] = compact('concrete', 'shared');
    }

    public function singleton($abstract, $concrete = null)
    {
        $this->bind($abstract, $concrete, true);
    }

    public function instance($abstract, $instance)
    {
        $this->removeAbstractAlias($abstract);

        $this->instances[$abstract] = $instance;

        if ($this->bound($abstract)) {
            $this->rebound($abstract);
        }
    }

    protected function removeAbstractAlias($searched)
    {
        if (!isset($this->aliases[$searched])) {
            return;
        }

        foreach ($this->aliases as $abstract => $alias) {
            if ($alias === $searched) {
                unset($this->aliases[$abstract]);
            }
        }
    }

    protected function dropStaleInstances($abstract)
    {
        unset($this->instances[$abstract]);
    }

    protected function rebound($abstract)
    {
        $instance = $this->make($abstract);

        foreach ($this->getReboundCallbacks($abstract) as $callback) {
            $callback($instance, $this);
        }
    }

    protected function getReboundCallbacks($abstract)
    {
        return [];
    }

    public function make($abstract, array $parameters = [])
    {
        return $this->resolve($abstract, $parameters);
    }

    public function get($id)
    {
        return $this->make($id);
    }

    public function resolve($abstract, $parameters = [])
    {
        $abstract = $this->getAlias($abstract);

        if (isset($this->instances[$abstract])) {
            return $this->instances[$abstract];
        }

        $concrete = $this->getConcrete($abstract);

        if ($this->isShared($abstract) && isset($this->instances[$abstract])) {
            return $this->instances[$abstract];
        }

        $object = $this->build($concrete, $parameters);

        if ($this->isShared($abstract)) {
            $this->instances[$abstract] = $object;
        }

        $this->resolved[$abstract] = true;

        return $object;
    }

    protected function getConcrete($abstract)
    {
        if (isset($this->bindings[$abstract])) {
            return $this->bindings[$abstract]['concrete'];
        }

        return $abstract;
    }

    protected function isShared($abstract)
    {
        return isset($this->instances[$abstract]) || (isset($this->bindings[$abstract]['shared']) && $this->bindings[$abstract]['shared'] === true);
    }

    public function build($concrete, array $parameters = [])
    {
        if ($concrete instanceof Closure) {
            return $concrete($this, $parameters);
        }

        $reflector = new ReflectionClass($concrete);

        if (!$reflector->isInstantiable()) {
            throw new \Exception("Target [{$concrete}] is not instantiable.");
        }

        $constructor = $reflector->getConstructor();

        if (is_null($constructor)) {
            return new $concrete;
        }

        $dependencies = $constructor->getParameters();
        $instances = $this->getDependencies($dependencies, $parameters);

        return $reflector->newInstanceArgs($instances);
    }

    protected function getDependencies(array $parameters, array $primitives = [])
    {
        $dependencies = [];

        foreach ($parameters as $parameter) {
            $type = $parameter->getType();

            if ($type && $type->isBuiltin()) {
                $dependencies[] = $primitives[$parameter->name] ?? null;
            } elseif ($type) {
                $dependencies[] = $this->make($type->getName());
            } else {
                $dependencies[] = $primitives[$parameter->name] ?? null;
            }
        }

        return $dependencies;
    }

    public function call($callback, array $parameters = [], $defaultMethod = null)
    {
        if ($callback instanceof \Closure) {
            return $callback($this, $parameters);
        }

        if (is_string($callback)) {
            return $this->callClass($callback, $parameters, $defaultMethod);
        }

        if (is_array($callback)) {
            return $this->callMethod($callback, $parameters);
        }

        throw new \Exception('Invalid callback provided.');
    }

    protected function callClass($callback, array $parameters, $defaultMethod)
    {
        [$class, $method] = $this->parseClassCallback($callback, $defaultMethod);

        return $this->call([$this->make($class), $method], $parameters);
    }

    protected function parseClassCallback($callback, $defaultMethod)
    {
        if (str_contains($callback, '@')) {
            return explode('@', $callback);
        }

        return [$callback, $defaultMethod];
    }

    protected function callMethod($callback, array $parameters)
    {
        [$instance, $method] = $callback;

        // Resolve method dependencies using reflection
        $dependencies = $this->resolveMethodDependencies($instance, $method, $parameters);

        return call_user_func_array([$instance, $method], $dependencies);
    }

    /**
     * Resolve the dependencies for a method.
     *
     * @param  object  $instance
     * @param  string  $method
     * @param  array  $parameters
     * @return array
     */
    protected function resolveMethodDependencies($instance, $method, array $parameters = [])
    {
        try {
            $reflector = new \ReflectionMethod($instance, $method);
        } catch (\ReflectionException $e) {
            return $parameters;
        }

        $dependencies = [];
        $methodParameters = $reflector->getParameters();

        foreach ($methodParameters as $parameter) {
            $type = $parameter->getType();

            // Check if we have this parameter value already
            if (isset($parameters[$parameter->getName()])) {
                $dependencies[] = $parameters[$parameter->getName()];
                continue;
            }

            // Check for indexed parameter
            if (isset($parameters[$parameter->getPosition()])) {
                $dependencies[] = $parameters[$parameter->getPosition()];
                continue;
            }

            // If parameter has a type hint
            if ($type && !$type->isBuiltin()) {
                $className = $type->getName();
                
                // Check if it's a FormRequest
                if (is_subclass_of($className, \Arpon\Foundation\Http\FormRequest::class)) {
                    $formRequest = $this->resolveFormRequest($className);
                    $dependencies[] = $formRequest;
                    continue;
                }

                // Resolve from container
                try {
                    $dependencies[] = $this->make($className);
                    continue;
                } catch (\Exception $e) {
                    // If can't resolve, check if optional
                    if ($parameter->isOptional()) {
                        $dependencies[] = $parameter->getDefaultValue();
                        continue;
                    }
                    throw $e;
                }
            }

            // Check if parameter has default value
            if ($parameter->isOptional()) {
                $dependencies[] = $parameter->getDefaultValue();
                continue;
            }

            // Use null for unresolved parameters
            $dependencies[] = null;
        }

        return $dependencies;
    }

    /**
     * Resolve a FormRequest and validate it.
     *
     * @param  string  $className
     * @return \Arpon\Foundation\Http\FormRequest
     */
    protected function resolveFormRequest($className)
    {
        $request = $this->make('request');
        
        // Create FormRequest from base request
        $formRequest = $className::createFrom($request);
        
        // Automatically validate
        $formRequest->validateResolved();
        
        return $formRequest;
    }

    public function alias($abstract, $alias)
    {
        $this->aliases[$alias] = $abstract;
    }

    protected function getAlias($abstract)
    {
        return isset($this->aliases[$abstract]) ? $this->aliases[$abstract] : $abstract;
    }

    public function forget($abstract)
    {
        unset($this->bindings[$abstract]);
        unset($this->instances[$abstract]);
        unset($this->resolved[$abstract]);
    }

    public function flush()
    {
        $this->bindings = [];
        $this->instances = [];
        $this->resolved = [];
    }

    public function offsetExists($offset)
    {
        return $this->bound($offset);
    }

    public function offsetGet($offset)
    {
        return $this->make($offset);
    }

    public function offsetSet($offset, $value)
    {
        $this->bind($offset, $value);
    }

    public function offsetUnset($offset)
    {
        unset($this->bindings[$offset]);
        unset($this->instances[$offset]);
        unset($this->resolved[$offset]);
    }

    public function __get($key)
    {
        return $this[$key];
    }

    public function __set($key, $value)
    {
        $this[$key] = $value;
    }
}
