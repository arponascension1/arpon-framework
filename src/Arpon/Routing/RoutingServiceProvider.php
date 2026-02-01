<?php

namespace Arpon\Routing;

use Arpon\Foundation\ServiceProvider;
use Arpon\Routing\Router;
use Arpon\Contracts\Routing\Router as RouterContract;
use Arpon\Http\Request;

class RoutingServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton('router', function ($app) {
            return new Router($app);
        });

        $this->app->singleton('request', function ($app) {
            return Request::capture();
        });

        $this->app->alias('router', Router::class);
        $this->app->alias('router', RouterContract::class);
    }
}
