<?php

namespace Arpon\Routing;

use Arpon\Http\Request;

class RouteCollection
{
    protected $routes = [];
    protected $allRoutes = [];
    protected $namedRoutes = [];
    protected $prioritized = false;

    public function add(Route $route)
    {
        $this->addToCollections($route);
        $this->addLookups($route);
        $this->prioritized = false;

        return $route;
    }

    public function remove(Route $route)
    {
        // Remove from method-based index
        foreach ($route->getMethods() as $method) {
            if (isset($this->routes[$method])) {
                $this->routes[$method] = array_values(array_filter(
                    $this->routes[$method],
                    fn ($r) => $r !== $route
                ));
            }
        }

        // Remove from named routes
        $name = $route->getName();
        if ($name && isset($this->namedRoutes[$name])) {
            unset($this->namedRoutes[$name]);
        }

        // Remove from flat list
        $key = array_search($route, $this->allRoutes, true);
        if ($key !== false) {
            unset($this->allRoutes[$key]);
            $this->allRoutes = array_values($this->allRoutes); // Re-index
        }
        
        $this->prioritized = false;
    }

    protected function addToCollections(Route $route)
    {
        foreach ($route->getMethods() as $method) {
            $this->routes[$method][] = $route;
        }

        $this->allRoutes[] = $route;
    }

    protected function addLookups(Route $route)
    {
        $name = $route->getName();

        if ($name) {
            $this->namedRoutes[$name] = $route;
        }
    }

    public function match(Request $request)
    {
        $routes = $this->get($request->getMethod());

        $route = $this->matchAgainstRoutes($routes, $request);

        return $route;
    }

    protected function matchAgainstRoutes($routes, Request $request)
    {
        foreach ($routes as $route) {
            if ($route->match($request)) {
                return $route;
            }
        }

        return null;
    }

    protected function prioritize()
    {
        foreach ($this->routes as $method => $routes) {
            usort($this->routes[$method], function ($a, $b) {
                return $this->compareRoutes($a, $b);
            });
        }
        $this->prioritized = true;
    }

    protected function compareRoutes($routeA, $routeB)
    {
        $segmentsA = explode('/', trim($routeA->getUri(), '/'));
        $segmentsB = explode('/', trim($routeB->getUri(), '/'));

        $len = max(count($segmentsA), count($segmentsB));

        for ($i = 0; $i < $len; $i++) {
            $segA = $segmentsA[$i] ?? null;
            $segB = $segmentsB[$i] ?? null;

            if ($segA === $segB) continue;

            // If lengths differ but prefix matched so far
            if ($segA === null) return 1; // A is shorter
            if ($segB === null) return -1; // B is shorter

            $isDynamicA = strpos($segA, '{') !== false;
            $isDynamicB = strpos($segB, '{') !== false;

            if (!$isDynamicA && $isDynamicB) return -1; // A is static, B is dynamic -> A first
            if ($isDynamicA && !$isDynamicB) return 1;  // A is dynamic, B is static -> B first
        }

        return 0;
    }

    public function get($method = null)
    {
        if (!$this->prioritized) {
            $this->prioritize();
        }

        if (is_null($method)) {
            return $this->routes;
        }

        $routes = $this->routes[$method] ?? [];

        // Include any catch-all routes (*)
        if ($method !== '*' && isset($this->routes['*'])) {
            $routes = array_merge($routes, $this->routes['*']);
        }

        return $routes;
    }

    public function getRoutes()
    {
        return $this->allRoutes;
    }

    public function getByName($name)
    {
        if (isset($this->namedRoutes[$name])) {
            return $this->namedRoutes[$name];
        }

        // Lazy search through all routes if not found in index
        foreach ($this->allRoutes as $route) {
            if ($route->getName() === $name) {
                $this->namedRoutes[$name] = $route;
                return $route;
            }
        }

        return null;
    }

    public function getRoutesByMethod()
    {
        return $this->routes;
    }
}