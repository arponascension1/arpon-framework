<?php

namespace Arpon\Console;

use Arpon\Foundation\ServiceProvider;

class ConsoleServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->registerCommands();
    }

    /**
     * Register the console commands.
     *
     * @return void
     */
    protected function registerCommands()
    {
        // Register built-in commands
        $this->commands([
            Commands\ListCommandsCommand::class,
            Commands\HelpCommand::class,
            Commands\MakeCommandCommand::class,
            Commands\ServeCommand::class,
            Commands\RouteListCommand::class,
            Commands\StorageLinkCommand::class,
            Commands\ConfigCacheCommand::class,
            Commands\ConfigClearCommand::class,
        ]);
    }

    /**
     * Register commands in the given array.
     *
     * @param  array  $commands
     * @return void
     */
    public function commands(array $commands)
    {
        foreach ($commands as $command) {
            $this->app->singleton($command, function ($app) use ($command) {
                $instance = new $command;
                if (method_exists($instance, 'setArponApplication')) {
                    $instance->setArponApplication($app);
                }
                return $instance;
            });
        }
    }
}
