<?php

namespace Arpon\Database;

use Arpon\Database\Eloquent\Model;
use Arpon\Database\Migrations\MigrationServiceProvider;
use Arpon\Foundation\ServiceProvider;
use Arpon\Database\DatabaseManager;
use Arpon\Database\Connectors\ConnectionFactory;

class DatabaseServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->registerDatabaseManager();
        $this->registerConnectionServices();
    }

    protected function registerDatabaseManager()
    {
        $this->app->singleton('db', function ($app) {
            // Pass the Application directly to DatabaseManager
            return new DatabaseManager($app, new ConnectionFactory());
        });

        $this->app->singleton('db.factory', function ($app) {
            return new ConnectionFactory();
        });
    }

    protected function registerConnectionServices()
    {
        // Register default connection
        $this->app->singleton('db.connection', function ($app) {
            return $app['db']->connection();
        });

        $this->app->singleton('db.schema', function ($app) {
            return $app['db']->connection()->getSchemaBuilder();
        });

        // Register specific connections if configured
        $connections = $this->app['config']->get('database.connections', []);
        
        foreach ($connections as $name => $connectionConfig) {
            $this->app->singleton("db.connection.{$name}", function ($app) use ($name) {
                return $app['db']->connection($name);
            });
        }
    }

    public function boot()
    {
        $this->registerConnectionServices();
        
        // Set up Eloquent Model connection resolver if Model class exists
        if (class_exists(Model::class)) {
            Model::setConnectionResolver($this->app['db']);
        }
        
        // Register migration services if migration service provider exists
        if (class_exists(MigrationServiceProvider::class)) {
            $this->app->register(new MigrationServiceProvider($this->app));
        }
    }
}
