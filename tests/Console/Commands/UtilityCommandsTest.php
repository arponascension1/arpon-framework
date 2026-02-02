<?php

namespace Tests\Console\Commands;

use PHPUnit\Framework\TestCase;
use Arpon\Foundation\Application;
use Arpon\Console\Commands\ConfigCacheCommand;
use Arpon\Console\Commands\ConfigClearCommand;
use Arpon\Console\Commands\KeyGenerateCommand;
use Arpon\Console\Commands\StorageLinkCommand;
use Arpon\Console\Commands\RouteListCommand;

class UtilityCommandsTest extends TestCase
{
    protected $app;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app = new Application(__DIR__ . '/../../..');
    }

    public function testConfigCacheCommandHasCorrectName()
    {
        $command = new ConfigCacheCommand();
        
        $this->assertEquals('config:cache', $command->getName());
    }

    public function testConfigClearCommandHasCorrectName()
    {
        $command = new ConfigClearCommand();
        
        $this->assertEquals('config:clear', $command->getName());
    }

    public function testKeyGenerateCommandHasCorrectName()
    {
        $command = new KeyGenerateCommand();
        
        $this->assertEquals('key:generate', $command->getName());
    }

    public function testStorageLinkCommandHasCorrectName()
    {
        $command = new StorageLinkCommand();
        
        $this->assertEquals('storage:link', $command->getName());
    }

    public function testRouteListCommandHasCorrectName()
    {
        $command = new RouteListCommand();
        
        $this->assertEquals('route:list', $command->getName());
    }

    public function testConfigCacheCommandHasAppProperty()
    {
        $command = new ConfigCacheCommand();
        $command->setArponApplication($this->app);

        $reflection = new \ReflectionClass($command);
        $property = $reflection->getProperty('app');
        $property->setAccessible(true);

        $this->assertSame($this->app, $property->getValue($command));
    }

    public function testConfigCacheCommandHasCallMethod()
    {
        $command = new ConfigCacheCommand();

        $this->assertTrue(method_exists($command, 'call'));
    }

    public function testConfigClearCommandHasAppProperty()
    {
        $command = new ConfigClearCommand();
        $command->setArponApplication($this->app);

        $reflection = new \ReflectionClass($command);
        $property = $reflection->getProperty('app');
        $property->setAccessible(true);

        $this->assertSame($this->app, $property->getValue($command));
    }

    public function testCommandsHaveHandleOrExecuteMethod()
    {
        $commands = [
            new ConfigCacheCommand(),
            new ConfigClearCommand(),
            new KeyGenerateCommand(),
            new StorageLinkCommand(),
            new RouteListCommand(),
        ];

        foreach ($commands as $command) {
            $hasMethod = method_exists($command, 'handle') || method_exists($command, 'execute');
            $this->assertTrue(
                $hasMethod,
                get_class($command) . ' should have a handle() or execute() method'
            );
        }
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->app = null;
    }
}
