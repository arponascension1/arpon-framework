<?php

namespace Arpon\Foundation\Configuration;

class Middleware
{
    protected $middleware = [];
    protected $middlewareGroups = [];
    public function web(array $middleware)
    {
        $this->middlewareGroups['web'] = $middleware;
        return $this;
    }

    public function api(array $middleware)
    {
        $this->middlewareGroups['api'] = $middleware;
        return $this;
    }

    public function alias($name, $class)
    {
        $this->middleware[$name] = $class;
        return $this;
    }

    public function getMiddleware()
    {
        return $this->middleware;
    }

    public function getMiddlewareGroups()
    {
        return $this->middlewareGroups;
    }
}
