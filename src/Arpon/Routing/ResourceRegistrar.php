<?php

namespace Arpon\Routing;

class ResourceRegistrar
{
    protected $router;
    protected $resourceName;
    protected $routes;

    public function __construct($router, $resourceName, array $routes)
    {
        $this->router = $router;
        $this->resourceName = $resourceName;
        $this->routes = $routes;
    }

    /**
     * Set the name prefix for all resource routes
     */
    public function name($prefix)
    {
        foreach ($this->routes as $route) {
            $currentName = $route->getName();
            
            // Replace the resource name prefix with the custom prefix
            if ($currentName) {
                $parts = explode('.', $currentName);
                if (count($parts) === 2 && $parts[0] === $this->resourceName) {
                    $newName = $prefix . '.' . $parts[1];
                    // Set the name directly to avoid prepending group prefix again
                    $action = $route->getAction();
                    if (is_array($action)) {
                        $action['as'] = $newName;
                        $route->setAction($action);
                    }
                }
            }
        }

        return $this;
    }

    /**
     * Add middleware to all resource routes
     */
    public function middleware($middleware)
    {
        foreach ($this->routes as $route) {
            $route->middleware($middleware);
        }

        return $this;
    }

    /**
     * Set the resource methods that should be registered
     *
     * @param array $methods
     * @return $this
     */
    public function only(array $methods)
    {
        foreach ($this->routes as $method => $route) {
            if (!in_array($method, $methods)) {
                $this->router->getRoutes()->remove($route);
                unset($this->routes[$method]);
            }
        }

        return $this;
    }

    /**
     * Set the resource methods that should not be registered
     *
     * @param array $methods
     * @return $this
     */
    public function except(array $methods)
    {
        foreach ($this->routes as $method => $route) {
            if (in_array($method, $methods)) {
                $this->router->getRoutes()->remove($route);
                unset($this->routes[$method]);
            }
        }

        return $this;
    }

    /**
     * Set where constraints for all resource routes
     */
    public function where($name, $expression = null)
    {
        foreach ($this->routes as $route) {
            $route->where($name, $expression);
        }

        return $this;
    }

    /**
     * Set the parameter names for the resource routes
     */
    public function parameters(array $parameters)
    {
        $parameter = $parameters[$this->resourceName] ?? null;

        if ($parameter) {
            // Calculate the original parameter name (singular of resource name)
            // Note: This is a simple approximation. For robust pluralization, use a helper.
            $originalParam = rtrim($this->resourceName, 's');
            
            foreach ($this->routes as $route) {
                $uri = $route->getUri();
                // Replace {post} with {article}
                $newUri = str_replace('{' . $originalParam . '}', '{' . $parameter . '}', $uri);
                $route->setUri($newUri);
            }
        }

        return $this;
    }

    /**
     * Get the underlying routes
     */
    public function getRoutes()
    {
        return $this->routes;
    }
}
