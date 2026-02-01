<?php

namespace Arpon\Foundation;

abstract class ServiceProvider
{
    protected $app;

    public function __construct($app)
    {
        $this->app = $app;
    }

    abstract public function register();

    public function boot()
    {
        //
    }

    public function provides()
    {
        return [];
    }

    public function isDeferred()
    {
        return array_diff($this->provides(), $this->app->loadedProviders);
    }
}
