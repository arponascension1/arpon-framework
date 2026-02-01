<?php

namespace Arpon\Routing;

use Arpon\Http\Request;
use Closure;


class Route
{
    protected $methods;
    protected $uri;
    protected $action;
    protected $parameters = [];
    protected $wheres = [];
    protected $middleware = [];
    protected $container;
    protected $where = [];
    protected $prefixApplied = false;

    public function __construct($methods, $uri, $action)
    {
        // Normalize URI to always start with /
        $this->uri = '/' . ltrim($uri, '/');
        $this->methods = (array) $methods;
        $this->action = $action;

        // Wrap non-Closure callables in 'uses' key if not already wrapped
        if (!($action instanceof \Closure) && !is_array($this->action)) {
            $this->action = ['uses' => $this->action];
        } elseif (is_array($this->action) && !isset($this->action['uses'])) {
            // Check if it's a [Controller::class, 'method'] format (indexed array with 2 elements)
            $keys = array_keys($this->action);
            if ($keys === [0, 1] && count($this->action) === 2) {
                // It's [Controller::class, 'method'] format
                $this->action = ['uses' => $this->action];
            }
        }
    }

    public function match(Request $request)
    {
        if (!$this->matchesMethod($request->getMethod())) {
            return false;
        }

        if (!$this->matchesUri($request->getPathInfo())) {
            return false;
        }

        return true;
    }

    protected function matchesMethod($method)
    {
        return in_array($method, $this->methods) || in_array('*', $this->methods);
    }

    protected function matchesUri($uri)
    {
        $pattern = $this->compileRoute($this->uri);

        if (!preg_match($pattern, $uri, $matches)) {
            return false;
        }

        $this->parameters = $this->extractParameters($matches);

        return true;
    }

    protected function compileRoute($uri)
    {
        // Handle optional parameters: /user/{name?} -> /user(?:/(?P<name>[^/]+))?
        $uri = preg_replace('/\/?\{([a-zA-Z_][a-zA-Z0-9_]*)\?\}/', '(?:/(?P<$1>[^/]+))?', $uri);
        
        // Handle custom constraints defined via where()
        foreach ($this->where as $param => $regex) {
            $uri = str_replace('{' . $param . '}', '(?P<' . $param . '>' . $regex . ')', $uri);
        }
        
        // Handle required parameters: /user/{id} -> /user/(?P<id>[^/]+)
        $uri = preg_replace('/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/', '(?P<$1>[^/]+)', $uri);
        
        // Handle custom regex parameters defined inline: {id:[0-9]+}
        $uri = preg_replace('/\{([a-zA-Z_][a-zA-Z0-9_]*):([^}]+)\}/', '(?P<$1>$2)', $uri);

        return '#^' . $uri . '/?$#';
    }

    protected function extractParameters($matches)
    {
        $parameters = [];

        foreach ($matches as $key => $value) {
            if (is_string($key)) {
                $parameters[$key] = $value;
            }
        }

        return $parameters;
    }

    public function run()
    {
        $this->container = app();

        if ($this->isControllerAction()) {
            return $this->runController();
        }

        return $this->runCallable();
    }

    public function isControllerAction()
    {
        return is_array($this->action) && isset($this->action['uses']) && is_string($this->action['uses']);
    }

    protected function runController()
    {
        $action = $this->action['uses'];

        if (strpos($action, '@') !== false) {
            [$controller, $method] = explode('@', $action);
        } else {
            $controller = $action;
            $method = '__invoke';
        }

        // Apply namespace if present and the controller class name doesn't already have a leading backslash
        if (isset($this->action['namespace']) && strpos($controller, '\\') !== 0) {
            $controller = trim($this->action['namespace'], '\\') . '\\' . $controller;
        }

        $instance = $this->container->make($controller);

        return $this->container->call([$instance, $method], $this->parameters);
    }

    protected function runCallable()
    {
        $callable = is_array($this->action) ? $this->action['uses'] ?? $this->action : $this->action;

        if ($callable instanceof \Closure || is_array($callable) || is_string($callable)) {
            return $this->container->call($callable, $this->resolveMethodDependencies());
        }

        throw new \Exception('Invalid route action: ' . print_r($callable, true));
    }

    public function middleware($middleware = null)
    {
        if (func_num_args() === 0) {
            // Getter
            return $this->middleware;
        }
        
        // Setter
        $this->middleware = array_merge($this->middleware, (array) $middleware);
        return $this;
    }

    public function getMiddleware()
    {
        return $this->middleware;
    }

    public function where($name, $expression = null)
    {
        if (is_array($name)) {
            $this->where = array_merge($this->where, $name);
        } else {
            $this->where[$name] = $expression;
        }

        return $this;
    }

    public function getUri()
    {
        return $this->uri;
    }

    public function setUri($uri)
    {
        $this->uri = '/' . ltrim($uri, '/');
        return $this;
    }

    public function getMethods()
    {
        return $this->methods;
    }

    public function getAction()
    {
        return $this->action;
    }

    protected function resolveMethodDependencies()
    {
        $callable = is_array($this->action) ? $this->action['uses'] ?? $this->action : $this->action;
        
        // Get reflection for the callable
        if ($callable instanceof \Closure) {
            $reflection = new \ReflectionFunction($callable);
        } elseif (is_array($callable) && count($callable) === 2) {
            $reflection = new \ReflectionMethod($callable[0], $callable[1]);
        } elseif (is_string($callable)) {
            if (strpos($callable, '@') !== false) {
                [$class, $method] = explode('@', $callable);
            } else {
                $class = $callable;
                $method = '__invoke';
            }
            
            if (class_exists($class) && method_exists($class, $method)) {
                $reflection = new \ReflectionMethod($class, $method);
            } else {
                return $this->parameters;
            }
        } else {
            return $this->parameters;
        }
        
        $resolvedParams = $this->parameters; // Start with all route parameters
        
        // We rely on Application::call to resolve dependencies like Request
        // But we need to ensure route parameters are keyed by name so Application::call can find them
        
        return $resolvedParams;
    }

    public function setAction($action)
    {
        $this->action = $action;
        
        // Apply prefix to URI if present in action and not already applied
        if (!$this->prefixApplied && is_array($action) && isset($action['prefix']) && !empty($action['prefix'])) {
            $prefix = trim($action['prefix'], '/');
            $uri = trim($this->uri, '/');
            $this->uri = '/' . ($prefix ? $prefix . '/' . $uri : $uri);
            $this->prefixApplied = true;
        }
        
        // Extract middleware from action if present
        if (is_array($action) && isset($action['middleware'])) {
            $this->middleware = array_merge(
                $this->middleware, 
                is_array($action['middleware']) ? $action['middleware'] : [$action['middleware']]
            );
        }
    }

    public function parameters($parameters = null)
    {
        if (is_null($parameters)) {
            return $this->parameters;
        }

        foreach ($parameters as $old => $new) {
            $this->uri = str_replace('{' . $old . '}', '{' . $new . '}', $this->uri);
            $this->uri = str_replace('{' . $old . '?}', '{' . $new . '?}', $this->uri);
        }

        return $this;
    }

    public function parameter($name, $default = null)
    {
        return $this->parameters[$name] ?? $default;
    }

    public function setParameter($name, $value)
    {
        $this->parameters[$name] = $value;
        return $this;
    }

    public function getName()
    {
        return is_array($this->action) ? ($this->action['as'] ?? null) : null;
    }

    public function name($name)
    {
        if (!is_array($this->action)) {
            $this->action = ['uses' => $this->action];
        }
        
        // Prepend group name prefix if it exists
        $groupPrefix = $this->action['as'] ?? '';
        $this->action['as'] = $groupPrefix . $name;
        
        return $this;
    }
}
