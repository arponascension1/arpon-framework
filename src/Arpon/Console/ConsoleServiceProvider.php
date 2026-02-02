<?php

namespace Arpon\Console;

use Arpon\Foundation\ServiceProvider;

class ConsoleServiceProvider extends ServiceProvider
{
    /**
     * All built-in console commands.
     *
     * @var array
     */
    protected $commands = [
        Commands\ListCommandsCommand::class,
        Commands\HelpCommand::class,
        Commands\MakeCommandCommand::class,
        Commands\ServeCommand::class,
        Commands\MigrateCommand::class,
        Commands\MigrateRollbackCommand::class,
        Commands\MigrateResetCommand::class,
        Commands\MigrateStatusCommand::class,
        Commands\MigrateInstallCommand::class,
        Commands\MigrateFreshCommand::class,
        Commands\MigrateRefreshCommand::class,
        Commands\MakeMigrationCommand::class,
        Commands\MakeRequestCommand::class,
        Commands\MakeControllerCommand::class,
        Commands\MakeModelCommand::class,
        Commands\KeyGenerateCommand::class,
        Commands\RouteListCommand::class,
        Commands\StorageLinkCommand::class,
        Commands\ConfigCacheCommand::class,
        Commands\ConfigClearCommand::class,
        Commands\SessionTableCommand::class,
        Commands\NotificationTableCommand::class,
    ];

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
        foreach ($this->commands as $command) {
            $this->app->singleton($command, function ($app) use ($command) {
                $instance = new $command;
                if (method_exists($instance, 'setArponApplication')) {
                    $instance->setArponApplication($app);
                }
                return $instance;
            });
        }
    }

    /**
     * Add commands to console application.
     *
     * @param  \Arpon\Console\Application  $console
     * @return void
     */
    public function addCommandsToConsole(Application $console)
    {
        foreach ($this->commands as $commandClass) {
            $command = $this->app->make($commandClass);
            $console->add($command);
        }
    }
}
