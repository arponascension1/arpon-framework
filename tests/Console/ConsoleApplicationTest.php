<?php

namespace Tests\Console;

use PHPUnit\Framework\TestCase;
use Arpon\Foundation\Application;
use Arpon\Console\Application as ConsoleApplication;

class ConsoleApplicationTest extends TestCase
{
    protected $app;
    protected $console;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app = new Application(__DIR__ . '/../..');
        $this->console = new ConsoleApplication($this->app);
    }

    public function testConsoleApplicationInitializes()
    {
        $this->assertInstanceOf(ConsoleApplication::class, $this->console);
    }

    public function testConsoleApplicationLoadsBuiltInCommands()
    {
        $commands = $this->console->all();

        $arponCommands = array_filter($commands, function($command) {
            return str_contains(get_class($command), 'Arpon\\Console\\Commands');
        });

        $this->assertGreaterThanOrEqual(18, count($arponCommands));
    }

    public function testConsoleApplicationHasAllExpectedCommands()
    {
        $expectedCommands = [
            'help',
            'list',
            'make:command',
            'make:controller',
            'make:model',
            'make:request',
            'make:migration',
            'migrate',
            'migrate:rollback',
            'migrate:reset',
            'migrate:status',
            'migrate:install',
            'config:cache',
            'config:clear',
            'route:list',
            'storage:link',
            'key:generate',
            'serve',
        ];

        foreach ($expectedCommands as $commandName) {
            $this->assertTrue(
                $this->console->has($commandName),
                "Console should have command: {$commandName}"
            );
        }
    }

    public function testConsoleApplicationHasCallMethod()
    {
        $this->assertTrue(
            method_exists($this->console, 'call'),
            'ConsoleApplication should have a call() method'
        );
    }

    public function testConsoleApplicationName()
    {
        $this->assertStringContainsString('Arpon', $this->console->getName());
    }

    public function testConsoleApplicationVersion()
    {
        $version = $this->console->getVersion();
        
        $this->assertNotEmpty($version);
        $this->assertMatchesRegularExpression('/^\d+\.\d+\.\d+/', $version);
    }

    public function testCommandsHaveArponApplicationSet()
    {
        $command = $this->console->find('config:cache');

        $reflection = new \ReflectionClass($command);
        $property = $reflection->getProperty('application');
        $property->setAccessible(true);
        $app = $property->getValue($command);

        $this->assertInstanceOf(Application::class, $app);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->app = null;
        $this->console = null;
    }
}
