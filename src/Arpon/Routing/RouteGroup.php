<?php

namespace Arpon\Routing;

class RouteGroup
{
    protected $router;
    protected $attributes = [];

    public function __construct(Router $router, array $attributes = [])
    {
        $this->router = $router;
        $this->attributes = $attributes;
    }

    public function middleware($middleware)
    {
        $this->attributes['middleware'] = is_array($middleware) ? $middleware : [$middleware];
        return $this;
    }

    public function prefix($prefix)
    {
        $this->attributes['prefix'] = $prefix;
        return $this;
    }

    public function name($name)
    {
        $this->attributes['as'] = $name;
        return $this;
    }

    public function namespace($namespace)
    {
        $this->attributes['namespace'] = $namespace;
        return $this;
    }

    public function where($name, $expression = null)
    {
        if (is_array($name)) {
            $this->attributes['where'] = array_merge($this->attributes['where'] ?? [], $name);
        } else {
            $this->attributes['where'][$name] = $expression;
        }
        return $this;
    }

    public function group($attributes = null, $callback = null)
    {
        // If called with two arguments: group(['middleware' => 'auth'], function() {})
        if (func_num_args() == 2) {
            $this->attributes = array_merge($this->attributes, $attributes);
            $this->router->groupWithAttributes($this->attributes, $callback);
        }
        // If called with one argument, and it's a closure: group(function() {})
        elseif (func_num_args() == 1 && $attributes instanceof \Closure) {
            $this->router->groupWithAttributes($this->attributes, $attributes);
        }
        // If called with one argument, and it's an array, wait for callback
        elseif (func_num_args() == 1 && is_array($attributes)) {
            $this->attributes = array_merge($this->attributes, $attributes);
            return $this;
        }
    }

    public function getAttributes()
    {
        return $this->attributes;
    }
}
