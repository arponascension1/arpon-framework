<?php

namespace Arpon\Log;

use Arpon\Foundation\ServiceProvider;

class LogServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton('log', function ($app) {
            return new Writer($app);
        });

        $this->app->alias('log', Writer::class);
    }
}
