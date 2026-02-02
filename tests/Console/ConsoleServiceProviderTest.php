<?php

namespace Tests\Console;

use PHPUnit\Framework\TestCase;
use Arpon\Foundation\Application;
use Arpon\Console\ConsoleServiceProvider;

class ConsoleServiceProviderTest extends TestCase
{
    protected $app;
    protected $provider;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app = new Application(__DIR__ . '/../..');
        $this->provider = new ConsoleServiceProvider($this->app);
    }

    public function testProviderRegistersAllCommands()
    {
        $this->provider->register();

        $expectedCommands = [
            \Arpon\Console\Commands\ListCommandsCommand::class,
            \Arpon\Console\Commands\HelpCommand::class,
            \Arpon\Console\Commands\MakeCommandCommand::class,
            \Arpon\Console\Commands\ServeCommand::class,
            \Arpon\Console\Commands\MigrateCommand::class,
            \Arpon\Console\Commands\MigrateRollbackCommand::class,
            \Arpon\Console\Commands\MigrateResetCommand::class,
            \Arpon\Console\Commands\MigrateStatusCommand::class,
            \Arpon\Console\Commands\MigrateInstallCommand::class,
            \Arpon\Console\Commands\MakeMigrationCommand::class,
            \Arpon\Console\Commands\MakeRequestCommand::class,
            \Arpon\Console\Commands\MakeControllerCommand::class,
            \Arpon\Console\Commands\MakeModelCommand::class,
            \Arpon\Console\Commands\KeyGenerateCommand::class,
            \Arpon\Console\Commands\RouteListCommand::class,
            \Arpon\Console\Commands\StorageLinkCommand::class,
            \Arpon\Console\Commands\ConfigCacheCommand::class,
            \Arpon\Console\Commands\ConfigClearCommand::class,
        ];

        foreach ($expectedCommands as $commandClass) {
            $this->assertTrue(
                $this->app->bound($commandClass),
                "Command {$commandClass} should be registered in the container"
            );
        }
    }

    public function testProviderRegistersExactly18Commands()
    {
        $reflection = new \ReflectionClass($this->provider);
        $property = $reflection->getProperty('commands');
        $property->setAccessible(true);
        $commands = $property->getValue($this->provider);

        $this->assertCount(18, $commands, 'ConsoleServiceProvider should register exactly 18 commands');
    }

    public function testCommandsAreRegisteredAsSingletons()
    {
        $this->provider->register();

        $command1 = $this->app->make(\Arpon\Console\Commands\HelpCommand::class);
        $command2 = $this->app->make(\Arpon\Console\Commands\HelpCommand::class);

        $this->assertSame($command1, $command2, 'Commands should be registered as singletons');
    }

    public function testCommandsHaveArponApplicationSet()
    {
        $this->provider->register();

        $command = $this->app->make(\Arpon\Console\Commands\HelpCommand::class);

        $reflection = new \ReflectionClass($command);
        $property = $reflection->getProperty('application');
        $property->setAccessible(true);
        $app = $property->getValue($command);

        $this->assertInstanceOf(Application::class, $app);
        $this->assertSame($this->app, $app);
    }

    public function testCommandsHaveAppPropertyAlias()
    {
        $this->provider->register();

        $command = $this->app->make(\Arpon\Console\Commands\ConfigCacheCommand::class);

        $reflection = new \ReflectionClass($command);
        $property = $reflection->getProperty('app');
        $property->setAccessible(true);
        $app = $property->getValue($command);

        $this->assertInstanceOf(Application::class, $app);
        $this->assertSame($this->app, $app);
    }

    public function testAddCommandsToConsole()
    {
        $this->provider->register();

        $console = new \Arpon\Console\Application($this->app);
        
        // Get all commands from console
        $commands = $console->all();
        
        $arponCommandCount = 0;
        foreach ($commands as $command) {
            if (strpos(get_class($command), 'Arpon\\Console\\Commands') !== false) {
                $arponCommandCount++;
            }
        }

        $this->assertEquals(18, $arponCommandCount, 'Console application should have 18 Arpon commands');
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->app = null;
        $this->provider = null;
    }
}
