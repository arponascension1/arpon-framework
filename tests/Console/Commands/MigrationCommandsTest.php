<?php

namespace Tests\Console\Commands;

use PHPUnit\Framework\TestCase;
use Arpon\Foundation\Application;
use Arpon\Console\Commands\MigrateCommand;
use Arpon\Console\Commands\MigrateRollbackCommand;
use Arpon\Console\Commands\MigrateResetCommand;
use Arpon\Console\Commands\MigrateStatusCommand;
use Arpon\Console\Commands\MigrateInstallCommand;

class MigrationCommandsTest extends TestCase
{
    protected $app;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app = new Application(__DIR__ . '/../../..');
    }

    public function testMigrateCommandHasCorrectName()
    {
        $command = new MigrateCommand();
        
        $this->assertEquals('migrate', $command->getName());
    }

    public function testMigrateRollbackCommandHasCorrectName()
    {
        $command = new MigrateRollbackCommand();
        
        $this->assertEquals('migrate:rollback', $command->getName());
    }

    public function testMigrateResetCommandHasCorrectName()
    {
        $command = new MigrateResetCommand();
        
        $this->assertEquals('migrate:reset', $command->getName());
    }

    public function testMigrateStatusCommandHasCorrectName()
    {
        $command = new MigrateStatusCommand();
        
        $this->assertEquals('migrate:status', $command->getName());
    }

    public function testMigrateInstallCommandHasCorrectName()
    {
        $command = new MigrateInstallCommand();
        
        $this->assertEquals('migrate:install', $command->getName());
    }

    public function testMigrationCommandsHaveHandleOrExecuteMethod()
    {
        $commands = [
            new MigrateCommand(),
            new MigrateRollbackCommand(),
            new MigrateResetCommand(),
            new MigrateStatusCommand(),
            new MigrateInstallCommand(),
        ];

        foreach ($commands as $command) {
            $hasMethod = method_exists($command, 'handle') || method_exists($command, 'execute');
            $this->assertTrue(
                $hasMethod,
                get_class($command) . ' should have a handle() or execute() method'
            );
        }
    }

    public function testMigrationCommandsHaveAppProperty()
    {
        $commands = [
            new MigrateCommand(),
            new MigrateRollbackCommand(),
            new MigrateResetCommand(),
        ];

        foreach ($commands as $command) {
            $command->setArponApplication($this->app);

            $reflection = new \ReflectionClass($command);
            $property = $reflection->getProperty('app');
            $property->setAccessible(true);

            $this->assertSame(
                $this->app,
                $property->getValue($command),
                get_class($command) . ' should have $app property set'
            );
        }
    }

    public function testMigrationCommandsExtendBaseCommand()
    {
        $commands = [
            new MigrateCommand(),
            new MigrateRollbackCommand(),
            new MigrateResetCommand(),
            new MigrateStatusCommand(),
            new MigrateInstallCommand(),
        ];

        foreach ($commands as $command) {
            $this->assertInstanceOf(
                \Arpon\Console\Command::class,
                $command,
                get_class($command) . ' should extend Arpon\Console\Command'
            );
        }
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->app = null;
    }
}
