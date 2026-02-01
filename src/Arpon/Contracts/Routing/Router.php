<?php

namespace Arpon\Contracts\Routing;

use Arpon\Routing\RouteGroup;

interface Router
{
    public function get($uri, $action);
    public function post($uri, $action);
    public function put($uri, $action);
    public function patch($uri, $action);
    public function delete($uri, $action);
    public function options($uri, $action);
    public function any($uri, $action);
    public function match($methods, $uri, $action);
    public function group($attributes, $routes);
    public function middleware($middleware);
    public function prefix($prefix);
    public function name($name);
    public function namespace($namespace);
    public function middlewareGroup($name, $middleware);
    public function routeMiddleware($middleware);
}
