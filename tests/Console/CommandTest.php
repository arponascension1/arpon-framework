<?php

namespace Tests\Console;

use PHPUnit\Framework\TestCase;
use Arpon\Foundation\Application;
use Arpon\Console\Command;
use Arpon\Console\Commands\HelpCommand;

class CommandTest extends TestCase
{
    protected $app;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app = new Application(__DIR__ . '/../..');
    }

    public function testCommandHasAppProperty()
    {
        $command = new HelpCommand();
        
        $this->assertObjectHasProperty('app', $command);
        $this->assertObjectHasProperty('application', $command);
    }

    public function testSetArponApplicationSetsAppProperty()
    {
        $command = new HelpCommand();
        $command->setArponApplication($this->app);

        $reflection = new \ReflectionClass($command);
        
        $appProperty = $reflection->getProperty('app');
        $app = $appProperty->getValue($command);

        $this->assertInstanceOf(Application::class, $app);
        $this->assertSame($this->app, $app);
    }

    public function testSetArponApplicationSetsApplicationProperty()
    {
        $command = new HelpCommand();
        $command->setArponApplication($this->app);

        $reflection = new \ReflectionClass($command);
        
        $applicationProperty = $reflection->getProperty('application');
        $application = $applicationProperty->getValue($command);

        $this->assertInstanceOf(Application::class, $application);
        $this->assertSame($this->app, $application);
    }

    public function testCommandHasCallMethod()
    {
        $command = new HelpCommand();

        $this->assertTrue(
            method_exists($command, 'call'),
            'Command should have a call() method'
        );
    }

    public function testCommandNameIsSetCorrectly()
    {
        $command = new HelpCommand();

        $this->assertNotEmpty($command->getName());
        $this->assertEquals('help', $command->getName());
    }

    public function testCommandWithNamePropertyInitializesCorrectly()
    {
        $command = new \Arpon\Console\Commands\ConfigCacheCommand();

        $this->assertNotNull($command->getName());
        $this->assertEquals('config:cache', $command->getName());
    }

    public function testCommandWithDescriptionSetsDescriptionCorrectly()
    {
        $command = new \Arpon\Console\Commands\ConfigCacheCommand();

        $this->assertNotEmpty($command->getDescription());
        $this->assertStringContainsString('cache', strtolower($command->getDescription()));
    }

    public function testCommandOutputMethods()
    {
        $command = new HelpCommand();

        $this->assertTrue(method_exists($command, 'info'));
        $this->assertTrue(method_exists($command, 'error'));
        $this->assertTrue(method_exists($command, 'warn'));
        $this->assertTrue(method_exists($command, 'comment'));
        $this->assertTrue(method_exists($command, 'question'));
        $this->assertTrue(method_exists($command, 'line'));
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->app = null;
    }
}
