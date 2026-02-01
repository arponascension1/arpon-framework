<?php

namespace Arpon\Cache;

use Arpon\Foundation\ServiceProvider;

class CacheServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('cache', function ($app) {
            return new CacheManager($app);
        });

        $this->app->singleton('cache.store', function ($app) {
            return $app['cache']->driver();
        });

        $this->app->alias('cache', CacheManager::class);
        $this->app->alias('cache.store', Repository::class);
        $this->app->alias('cache.store', \Arpon\Contracts\Cache\Repository::class);
    }
}
