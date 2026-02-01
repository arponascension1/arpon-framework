<?php

namespace Arpon\Validation;

use Arpon\Foundation\ServiceProvider;

class ValidationServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton('validator', function ($app) {
            return new Factory($app);
        });
    }

    public function boot()
    {
        //
    }
}
