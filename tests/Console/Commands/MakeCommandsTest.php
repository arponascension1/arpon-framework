<?php

namespace Tests\Console\Commands;

use PHPUnit\Framework\TestCase;
use Arpon\Foundation\Application;
use Arpon\Console\Commands\MakeCommandCommand;
use Arpon\Console\Commands\MakeControllerCommand;
use Arpon\Console\Commands\MakeModelCommand;
use Arpon\Console\Commands\MakeRequestCommand;
use Arpon\Console\Commands\MakeMigrationCommand;

class MakeCommandsTest extends TestCase
{
    protected $app;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app = new Application(__DIR__ . '/../../..');
    }

    public function testMakeCommandCommandHasCorrectName()
    {
        $command = new MakeCommandCommand();
        
        $this->assertEquals('make:command', $command->getName());
    }

    public function testMakeControllerCommandHasCorrectName()
    {
        $command = new MakeControllerCommand();
        
        $this->assertEquals('make:controller', $command->getName());
    }

    public function testMakeModelCommandHasCorrectName()
    {
        $command = new MakeModelCommand();
        
        $this->assertEquals('make:model', $command->getName());
    }

    public function testMakeRequestCommandHasCorrectName()
    {
        $command = new MakeRequestCommand();
        
        $this->assertEquals('make:request', $command->getName());
    }

    public function testMakeMigrationCommandHasCorrectName()
    {
        $command = new MakeMigrationCommand();
        
        $this->assertEquals('make:migration', $command->getName());
    }

    public function testMakeCommandsHaveHandleOrExecuteMethod()
    {
        $commands = [
            new MakeCommandCommand(),
            new MakeControllerCommand(),
            new MakeModelCommand(),
            new MakeRequestCommand(),
            new MakeMigrationCommand(),
        ];

        foreach ($commands as $command) {
            $hasMethod = method_exists($command, 'handle') || method_exists($command, 'execute');
            $this->assertTrue(
                $hasMethod,
                get_class($command) . ' should have a handle() or execute() method'
            );
        }
    }

    public function testMakeCommandsHaveAppProperty()
    {
        $commands = [
            new MakeCommandCommand(),
            new MakeControllerCommand(),
            new MakeModelCommand(),
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

    public function testMakeCommandsExtendBaseCommand()
    {
        $commands = [
            new MakeCommandCommand(),
            new MakeControllerCommand(),
            new MakeModelCommand(),
            new MakeRequestCommand(),
            new MakeMigrationCommand(),
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
