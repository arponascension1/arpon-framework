<?php

namespace Arpon\Routing;

use Arpon\Http\Request;
use Arpon\Http\Response;
use Arpon\Routing\RouteCollection;
use Arpon\Routing\Route;
use Arpon\Routing\RouteGroup;
use Arpon\Routing\Exceptions\RouteNotFoundException;
use Closure;

class Router
{
    protected $app;
    protected $routes;
    protected $currentRoute;
    protected $currentRequest;
    protected $middleware = [];
    protected $middlewareGroups = [];
    protected $routeMiddleware = [];
    protected $groupStack = [];
    protected $currentRouteType = 'web'; // Track if loading web or api routes

    public function __construct($app)
    {
        $this->app = $app;
        $this->routes = new RouteCollection();
    }

    public function setMiddlewareGroups(array $groups)
    {
        $this->middlewareGroups = $groups;
        return $this;
    }

    public function setRouteMiddleware(array $middleware)
    {
        $this->routeMiddleware = $middleware;
        return $this;
    }

    public function get($uri, $action)
    {
        return $this->addRoute(['GET', 'HEAD'], $uri, $action);
    }

    public function post($uri, $action)
    {
        return $this->addRoute('POST', $uri, $action);
    }

    public function put($uri, $action)
    {
        return $this->addRoute('PUT', $uri, $action);
    }

    public function patch($uri, $action)
    {
        return $this->addRoute('PATCH', $uri, $action);
    }

    public function delete($uri, $action)
    {
        return $this->addRoute('DELETE', $uri, $action);
    }

    public function options($uri, $action)
    {
        return $this->addRoute('OPTIONS', $uri, $action);
    }

    public function any($uri, $action)
    {
        return $this->addRoute('*', $uri, $action);
    }

    public function match($methods, $uri, $action)
    {
        return $this->addRoute(array_map('strtoupper', (array) $methods), $uri, $action);
    }

    protected function addRoute($methods, $uri, $action)
    {
        $route = $this->createRoute($methods, $uri, $action);
        $this->routes->add($route);
        return $route;
    }

    protected function createRoute($methods, $uri, $action)
    {
        if ($this->actionReferencesController($action)) {
            $action = $this->convertToControllerAction($action);
        }

        $route = new Route($methods, $uri, $action);

        // Add default middleware group based on current route type
        $route->middleware($this->currentRouteType);

        if ($this->hasGroupStack()) {
            $this->mergeGroupAttributesIntoRoute($route);
        }

        return $route;
    }

    protected function actionReferencesController($action)
    {
        if ($action instanceof Closure) {
            return false;
        }

        return is_string($action) || (is_array($action) && isset($action['uses']));
    }

    protected function convertToControllerAction($action)
    {
        if (is_string($action)) {
            $action = ['uses' => $action];
        }

        return $action;
    }

    protected function hasGroupStack()
    {
        return !empty($this->groupStack);
    }

    protected function mergeGroupAttributesIntoRoute($route)
    {
        $action = $route->getAction();
        // Ensure action is an array, preserving Closure in 'uses' key if needed
        if (!is_array($action)) {
            $action = ['uses' => $action];
        }
        $route->setAction($this->mergeWithLastGroup($action));
    }

    protected function mergeWithLastGroup($newAttributes)
    {
        $lastGroup = !empty($this->groupStack) ? end($this->groupStack) : [];
        return $this->mergeGroupAttributes($newAttributes, $lastGroup);
    }

    protected function mergeGroupAttributes($new, $old)
    {
        // Ensure $old is an array, not a closure
        if (!is_array($old)) {
            $old = [];
        }
        
        $new['namespace'] = $this->formatNamespace($new, $old);
        $new['prefix'] = $this->formatPrefix($new, $old);
        $new['as'] = $this->formatAs($new, $old);
        $new['where'] = array_merge($old['where'] ?? [], $new['where'] ?? []);
        
        // Merge middleware from group
        if (isset($old['middleware'])) {
            $oldMiddleware = is_array($old['middleware']) ? $old['middleware'] : [$old['middleware']];
            $newMiddleware = isset($new['middleware']) ? (is_array($new['middleware']) ? $new['middleware'] : [$new['middleware']]) : [];
            $new['middleware'] = array_merge($oldMiddleware, $newMiddleware);
        }

        return $new;
    }

    protected function formatNamespace($new, $old)
    {
        // Ensure $old is an array
        if (!is_array($old)) {
            $old = [];
        }
        
        if (isset($new['namespace'])) {
            return isset($old['namespace']) && strpos($new['namespace'], '\\') !== 0
                ? trim($old['namespace'], '\\') . '\\' . trim($new['namespace'], '\\')
                : trim($new['namespace'], '\\');
        }

        return $old['namespace'] ?? null;
    }

    protected function formatPrefix($new, $old)
    {
        // Ensure $old is an array
        if (!is_array($old)) {
            $old = [];
        }
        
        $oldPrefix = $old['prefix'] ?? '';
        $newPrefix = $new['prefix'] ?? '';

        return trim($oldPrefix, '/') . '/' . trim($newPrefix, '/');
    }

    protected function formatAs($new, $old)
    {
        // Ensure $old is an array
        if (!is_array($old)) {
            $old = [];
        }
        
        $oldAs = $old['as'] ?? '';
        $newAs = $new['as'] ?? '';

        return $oldAs . $newAs;
    }

    public function middleware($middleware)
    {
        return new RouteGroup($this, ['middleware' => is_array($middleware) ? $middleware : [$middleware]]);
    }

    public function prefix($prefix)
    {
        return new RouteGroup($this, ['prefix' => $prefix]);
    }

    public function name($name)
    {
        return new RouteGroup($this, ['as' => $name]);
    }

    public function namespace($namespace)
    {
        return new RouteGroup($this, ['namespace' => $namespace]);
    }

    public function group($attributes, $routes = null)
    {
        // If only one argument and it's a closure, use it directly
        if (func_num_args() == 1 && $attributes instanceof Closure) {
            $this->groupWithAttributes([], $attributes);
        } else {
            $this->groupWithAttributes($attributes, $routes);
        }
    }

    public function groupWithAttributes($attributes, $callback)
    {
        $this->updateGroupStack($attributes);
        $callback($this);
        array_pop($this->groupStack);
    }

    protected function updateGroupStack($attributes)
    {
        if (!empty($this->groupStack)) {
            $lastGroup = end($this->groupStack);
            // Ensure the last group is an array, not a closure
            if (!is_array($lastGroup)) {
                // Remove the invalid item from the stack
                array_pop($this->groupStack);
                $lastGroup = [];
            }
            $attributes = $this->mergeGroupAttributes($attributes, $lastGroup);
        }

        $this->groupStack[] = $attributes;
    }

    public function dispatch(Request $request)
    {
        $this->currentRequest = $request;

        $response = $this->dispatchToRoute($request);

        return $response;
    }

    protected function dispatchToRoute(Request $request)
    {
        $route = $this->findRoute($request);

        $request->setRouteResolver(function () use ($route) {
            return $route;
        });

        $this->currentRoute = $route;

        return $this->runRoute($request, $route);
    }

    protected function findRoute($request)
    {
        $routes = $this->routes->get($request->getMethod());
        
        foreach ($routes as $route) {
            if ($route->match($request)) {
                return $route;
            }
        }

        throw new RouteNotFoundException($request->getPathInfo(), $request->getMethod());
    }

    protected function runRoute(Request $request, Route $route)
    {
        $request->setRouteResolver(function () use ($route) {
            return $route;
        });

        return $this->runRouteWithinStack($route, $request);
    }

    protected function runRouteWithinStack(Route $route, Request $request)
    {
        $middleware = $this->gatherRouteMiddleware($route);

        return (new Pipeline($this->app))
            ->send($request)
            ->through($middleware)
            ->then(function ($request) use ($route) {
                return $this->prepareResponse($request, $route->run());
            });
    }

    protected function gatherRouteMiddleware(Route $route)
    {
        $middleware = [];
        $routeMiddleware = $route->middleware();

        // If the route is a controller action, we'll check for controller middleware
        if ($route->isControllerAction()) {
            $routeMiddleware = array_merge($routeMiddleware, $this->getControllerMiddleware($route));
        }

        foreach ($routeMiddleware as $name) {
            // Split name and parameters
            [$name, $parameters] = $this->parseMiddleware($name);

            // Check if it's a middleware group
            if (isset($this->middlewareGroups[$name])) {
                $groupMiddleware = $this->middlewareGroups[$name];
                foreach ($groupMiddleware as $groupItem) {
                    // Groups can also have parameterized middleware
                    [$groupName, $groupParams] = $this->parseMiddleware($groupItem);
                    $middleware[] = $this->resolveMiddleware($groupName, array_merge($parameters, $groupParams));
                }
            } else {
                $middleware[] = $this->resolveMiddleware($name, $parameters);
            }
        }

        return $middleware;
    }

    protected function getControllerMiddleware(Route $route)
    {
        $action = $route->getAction();
        $uses = $action['uses'];

        if (strpos($uses, '@') !== false) {
            [$controller, $method] = explode('@', $uses);
        } else {
            $controller = $uses;
            $method = '__invoke';
        }

        // Apply namespace if present
        if (isset($action['namespace']) && strpos($controller, '\\') !== 0) {
            $controller = trim($action['namespace'], '\\') . '\\' . $controller;
        }

        if (! class_exists($controller)) {
            return [];
        }

        $instance = $this->app->make($controller);

        $results = [];

        // Support Laravel 12 style HasMiddleware interface
        if ($instance instanceof \Arpon\Contracts\Routing\HasMiddleware) {
            foreach ($instance::middleware() as $middleware) {
                if ($middleware instanceof \Arpon\Routing\Controllers\Middleware) {
                    if ($this->methodExcludedByOptions($method, ['only' => $middleware->only, 'except' => $middleware->except])) {
                        continue;
                    }
                    $results[] = $middleware->middleware;
                } else {
                    $results[] = $middleware;
                }
            }
        }

        // Fallback for old base controller or custom implementation
        if (method_exists($instance, 'getMiddleware')) {
            foreach ($instance->getMiddleware() as $middleware) {
                if ($this->methodExcludedByOptions($method, $middleware['options'])) {
                    continue;
                }

                $results[] = $middleware['middleware'];
            }
        }

        return array_unique($results);
    }

    protected function methodExcludedByOptions($method, array $options)
    {
        return (isset($options['only']) && ! in_array($method, (array) $options['only'])) ||
               (! empty($options['except']) && in_array($method, (array) $options['except']));
    }

    protected function parseMiddleware($name)
    {
        if (strpos($name, ':') === false) {
            return [$name, []];
        }

        [$name, $parameters] = explode(':', $name, 2);

        return [$name, explode(',', $parameters)];
    }

    protected function resolveMiddleware($name, $parameters = [])
    {
        $middleware = $this->getMiddlewareByName($name);

        if (empty($parameters)) {
            return $middleware;
        }

        // If there are parameters, we wrap the middleware in a closure
        return function ($request, $next) use ($middleware, $parameters) {
            $instance = is_object($middleware) ? $middleware : $this->app->make($middleware);
            
            return $this->app->call([$instance, 'handle'], array_merge([$request, $next], $parameters));
        };
    }

    protected function getMiddlewareByName($name)
    {
        if (isset($this->middleware[$name])) {
            return $this->middleware[$name];
        }

        if (isset($this->routeMiddleware[$name])) {
            return $this->routeMiddleware[$name];
        }

        // If it's a class name, return it directly
        if (class_exists($name)) {
            return $name;
        }

        throw new \Exception("Middleware [{$name}] not found.");
    }

    protected function prepareResponse($request, $response)
    {
        if ($response instanceof Response) {
            return $response;
        }

        return new Response($response);
    }

    public function middlewareGroup($name, $middleware)
    {
        $this->middlewareGroups[$name] = $middleware;
    }

    public function routeMiddleware($middleware)
    {
        $this->routeMiddleware = array_merge($this->routeMiddleware, $middleware);
    }

    public function resource($name, $controller, array $options = [])
    {
        $only = $options['only'] ?? null;
        $except = $options['except'] ?? null;
        $names = $options['names'] ?? [];
        $parameters = $options['parameters'] ?? [];
        $as = $options['as'] ?? null; // Name prefix

        // Default parameter name (singular form of resource name)
        $parameterName = $parameters[$name] ?? str_replace('-', '_', rtrim($name, 's'));

        $routes = [
            'index' => [['GET', 'HEAD'], '', 'index'],
            'create' => [['GET', 'HEAD'], '/create', 'create'],
            'store' => ['POST', '', 'store'],
            'show' => [['GET', 'HEAD'], '/{' . $parameterName . '}', 'show'],
            'edit' => [['GET', 'HEAD'], '/{' . $parameterName . '}/edit', 'edit'],
            'update' => ['PUT', '/{' . $parameterName . '}', 'update'],
            'destroy' => ['DELETE', '/{' . $parameterName . '}', 'destroy'],
        ];

        $createdRoutes = [];

        foreach ($routes as $routeName => $route) {
            if ($only && !in_array($routeName, (array)$only)) {
                continue;
            }
            if ($except && in_array($routeName, (array)$except)) {
                continue;
            }

            [$method, $uri, $action] = $route;
            $fullUri = '/' . trim($name, '/') . $uri;
            
            // Use custom name if provided, otherwise use default
            $routeNameFull = $names[$routeName] ?? ($as ? $as . '.' . $routeName : $name . '.' . $routeName);
            
            $routeAction = is_string($controller) ? $controller . '@' . $action : [$controller, $action];

            $routeInstance = $this->match((array)$method, $fullUri, $routeAction)
                ->name($routeNameFull);
            
            $createdRoutes[$routeName] = $routeInstance;
        }

        // Return a ResourceRegistrar to allow chaining
        return new ResourceRegistrar($this, $name, $createdRoutes);
    }

    public function apiResource($name, $controller, array $options = [])
    {
        // Exclude create and edit for API resources
        $options['except'] = array_merge($options['except'] ?? [], ['create', 'edit']);
        return $this->resource($name, $controller, $options);
    }

    public function resources(array $resources, array $options = [])
    {
        foreach ($resources as $name => $controller) {
            $this->resource($name, $controller, $options);
        }
        
        return $this;
    }

    public function fallback($action)
    {
        $placeholder = 'fallbackPlaceholder';
        
        return $this->any('/{' . $placeholder . '}', $action)
            ->where($placeholder, '.*')
            ->name('fallback');
    }

    public function getRoutes()
    {
        return $this->routes;
    }

    public function getCurrentRoute()
    {
        return $this->currentRoute;
    }

    public function setCurrentRouteType($type)
    {
        $this->currentRouteType = $type;
        return $this;
    }

    public function getCurrentRouteType()
    {
        return $this->currentRouteType;
    }
}
