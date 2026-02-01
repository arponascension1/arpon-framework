<?php

namespace Arpon\Filesystem;

use Arpon\Foundation\ServiceProvider;

class FilesystemServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton('filesystem', function ($app) {
            return new FilesystemManager($app->make('config')->get('filesystems', []));
        });

        // Alias for Storage facade
        $this->app->alias('filesystem', 'storage');
    }

    public function boot()
    {
        //
    }
}
