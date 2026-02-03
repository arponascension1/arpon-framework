<?php

namespace Arpon\Foundation\Configuration;

class Middleware
{
    protected $middleware = [];
    protected $middlewareGroups = [];
    protected $middlewarePriority = [];
    protected $globalMiddleware = [];
    protected $excludedMiddleware = [];
    protected $appendedMiddleware = [];
    protected $prependedMiddleware = [];
    
    /**
     * Register global middleware that runs on every request
     */
    public function use(array|string $middleware): static
    {
        $middlewares = is_array($middleware) ? $middleware : [$middleware];
        
        foreach ($middlewares as $mw) {
            if (!in_array($mw, $this->globalMiddleware)) {
                $this->globalMiddleware[] = $mw;
            }
        }
        
        return $this;
    }
    
    /**
     * Register web middleware group
     */
    public function web(array $middleware): static
    {
        $this->middlewareGroups['web'] = $middleware;
        return $this;
    }

    /**
     * Register API middleware group
     */
    public function api(array $middleware): static
    {
        $this->middlewareGroups['api'] = $middleware;
        return $this;
    }
    
    /**
     * Create a custom middleware group
     */
    public function group(string $name, array $middleware): static
    {
        $this->middlewareGroups[$name] = $middleware;
        return $this;
    }
    
    /**
     * Append middleware to a group
     */
    public function appendToGroup(string $group, array|string $middleware): static
    {
        $middlewares = is_array($middleware) ? $middleware : [$middleware];
        
        if (!isset($this->middlewareGroups[$group])) {
            $this->middlewareGroups[$group] = [];
        }
        
        foreach ($middlewares as $mw) {
            if (!in_array($mw, $this->middlewareGroups[$group])) {
                $this->middlewareGroups[$group][] = $mw;
            }
        }
        
        return $this;
    }
    
    /**
     * Prepend middleware to a group
     */
    public function prependToGroup(string $group, array|string $middleware): static
    {
        $middlewares = is_array($middleware) ? $middleware : [$middleware];
        
        if (!isset($this->middlewareGroups[$group])) {
            $this->middlewareGroups[$group] = [];
        }
        
        foreach (array_reverse($middlewares) as $mw) {
            if (!in_array($mw, $this->middlewareGroups[$group])) {
                array_unshift($this->middlewareGroups[$group], $mw);
            }
        }
        
        return $this;
    }
    
    /**
     * Remove middleware from a group
     */
    public function removeFromGroup(string $group, array|string $middleware): static
    {
        $middlewares = is_array($middleware) ? $middleware : [$middleware];
        
        if (isset($this->middlewareGroups[$group])) {
            $this->middlewareGroups[$group] = array_values(
                array_diff($this->middlewareGroups[$group], $middlewares)
            );
        }
        
        return $this;
    }
    
    /**
     * Replace middleware in a group
     */
    public function replaceInGroup(string $group, string $search, string $replace): static
    {
        if (isset($this->middlewareGroups[$group])) {
            $key = array_search($search, $this->middlewareGroups[$group]);
            if ($key !== false) {
                $this->middlewareGroups[$group][$key] = $replace;
            }
        }
        
        return $this;
    }

    /**
     * Register middleware aliases
     */
    public function alias($name, $class = null): static
    {
        // If an array is passed, register multiple aliases
        if (is_array($name)) {
            foreach ($name as $alias => $middleware) {
                $this->middleware[$alias] = $middleware;
            }
        } else {
            // Register a single alias
            $this->middleware[$name] = $class;
        }
        
        return $this;
    }
    
    /**
     * Set middleware priority for sorting
     */
    public function priority(array $middleware): static
    {
        $this->middlewarePriority = $middleware;
        return $this;
    }
    
    /**
     * Exclude middleware from certain routes
     */
    public function except(array|string $middleware): static
    {
        $middlewares = is_array($middleware) ? $middleware : [$middleware];
        
        foreach ($middlewares as $mw) {
            if (!in_array($mw, $this->excludedMiddleware)) {
                $this->excludedMiddleware[] = $mw;
            }
        }
        
        return $this;
    }
    
    /**
     * Append middleware to the end
     */
    public function append(array|string $middleware): static
    {
        $middlewares = is_array($middleware) ? $middleware : [$middleware];
        
        foreach ($middlewares as $mw) {
            $this->appendedMiddleware[] = $mw;
        }
        
        return $this;
    }
    
    /**
     * Prepend middleware to the beginning
     */
    public function prepend(array|string $middleware): static
    {
        $middlewares = is_array($middleware) ? $middleware : [$middleware];
        
        foreach (array_reverse($middlewares) as $mw) {
            array_unshift($this->prependedMiddleware, $mw);
        }
        
        return $this;
    }
    
    /**
     * Disable a middleware alias
     */
    public function disable(array|string $middleware): static
    {
        $middlewares = is_array($middleware) ? $middleware : [$middleware];
        
        foreach ($middlewares as $mw) {
            if (isset($this->middleware[$mw])) {
                unset($this->middleware[$mw]);
            }
        }
        
        return $this;
    }
    
    /**
     * Check if middleware alias exists
     */
    public function hasAlias(string $alias): bool
    {
        return isset($this->middleware[$alias]);
    }
    
    /**
     * Get middleware class by alias
     */
    public function getAlias(string $alias): ?string
    {
        return $this->middleware[$alias] ?? null;
    }
    
    /**
     * Get all middleware aliases
     */
    public function getMiddleware(): array
    {
        return $this->middleware;
    }

    /**
     * Get all middleware groups
     */
    public function getMiddlewareGroups(): array
    {
        return $this->middlewareGroups;
    }
    
    /**
     * Get specific middleware group
     */
    public function getGroup(string $name): array
    {
        return $this->middlewareGroups[$name] ?? [];
    }
    
    /**
     * Get global middleware
     */
    public function getGlobalMiddleware(): array
    {
        return $this->globalMiddleware;
    }
    
    /**
     * Get middleware priority
     */
    public function getPriority(): array
    {
        return $this->middlewarePriority;
    }
    
    /**
     * Get excluded middleware
     */
    public function getExcluded(): array
    {
        return $this->excludedMiddleware;
    }
    
    /**
     * Check if a group exists
     */
    public function hasGroup(string $name): bool
    {
        return isset($this->middlewareGroups[$name]);
    }
}

