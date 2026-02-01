<?php

namespace Arpon\Database\Migrations;

use Arpon\Foundation\ServiceProvider;

class MigrationServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->registerRepository();
        $this->registerMigrator();
    }

    /**
     * Register the migration repository.
     *
     * @return void
     */
    protected function registerRepository()
    {
        $this->app->singleton('migration.repository', function ($app) {
            $connection = $app['db']->connection();
            $table = $app['config']->get('database.migrations', 'migrations');
            
            return new DatabaseMigrationRepository($connection, $table);
        });
    }

    /**
     * Register the migrator.
     *
     * @return void
     */
    protected function registerMigrator()
    {
        $this->app->singleton('migrator', function ($app) {
            $repository = $app['migration.repository'];
            
            return new Migrator($app['db'], $repository);
        });
    }
}
