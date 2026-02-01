<?php

namespace Arpon\View;

use Arpon\Foundation\ServiceProvider;

class ViewServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton('view', function ($app) {
            $config = $app->make('config')->get('view');
            $paths = $config['paths'] ?? [];
            $compiled = $config['compiled'] ?? null;

            return new Factory($app, $paths, $compiled);
        });

        $this->app->singleton('view.helper', function ($app) {
            return new ViewHelper();
        });
    }
}
