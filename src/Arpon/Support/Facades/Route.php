<?php

namespace Arpon\Support\Facades;

use Arpon\Routing\ResourceRegistrar;
use Arpon\Routing\Route as RouteInstance;
use Arpon\Routing\RouteCollection;
use Arpon\Routing\Router;
use Arpon\Routing\RouteGroup;

/**
 * @method static RouteInstance get(string $uri, \Closure|array|string|null $action = null)
 * @method static RouteInstance post(string $uri, \Closure|array|string|null $action = null)
 * @method static RouteInstance put(string $uri, \Closure|array|string|null $action = null)
 * @method static RouteInstance patch(string $uri, \Closure|array|string|null $action = null)
 * @method static RouteInstance delete(string $uri, \Closure|array|string|null $action = null)
 * @method static RouteInstance options(string $uri, \Closure|array|string|null $action = null)
 * @method static RouteInstance any(string $uri, \Closure|array|string|null $action = null)
 * @method static RouteInstance match(array|string $methods, string $uri, \Closure|array|string|null $action = null)
 * @method static ResourceRegistrar resource(string $name, string $controller, array $options = [])
 * @method static ResourceRegistrar apiResource(string $name, string $controller, array $options = [])
 * @method static Router resources(array $resources, array $options = [])
 * @method static RouteGroup middleware(string|array $middleware)
 * @method static RouteGroup prefix(string $prefix)
 * @method static RouteGroup name(string $name)
 * @method static RouteGroup namespace(string $namespace)
 * @method static void group(array $attributes, \Closure $callback)
 * @method static mixed dispatch(\Arpon\Http\Request $request)
 * @method static RouteCollection getRoutes()
 * @method static void setMiddlewareGroups(array $middlewareGroups)
 * @method static void setRouteMiddleware(array $middleware)
 *
 * @see \Arpon\Routing\Router
 * @see \Arpon\Routing\Route
 * @see \Arpon\Routing\RouteGroup
 */
class Route extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'router';
    }
}
