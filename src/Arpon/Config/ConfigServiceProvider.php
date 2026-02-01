<?php

namespace Arpon\Config;

use Arpon\Foundation\ServiceProvider;
use Arpon\Config\Repository;

class ConfigServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton('config', function ($app) {
            return new LazyConfigRepository($app);
        });
    }
}
