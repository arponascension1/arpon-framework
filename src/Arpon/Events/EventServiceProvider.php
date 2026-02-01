<?php

namespace Arpon\Events;

use Arpon\Foundation\ServiceProvider;
use Arpon\Events\Dispatcher;
use Arpon\Contracts\Events\Dispatcher as DispatcherContract;

class EventServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton('events', function ($app) {
            return new Dispatcher($app);
        });

        $this->app->alias('events', Dispatcher::class);
        $this->app->alias('events', DispatcherContract::class);
    }
}
