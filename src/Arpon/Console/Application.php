<?php

namespace Arpon\Console;

use Arpon\Foundation\Application as FoundationApplication;
use Symfony\Component\Console\Application as SymfonyApplication;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command as SymfonyCommand;

class Application extends SymfonyApplication
{
    /**
     * The Laravel application instance.
     *
     * @var \Arpon\Foundation\Application
     */
    protected $laravel;

    /**
     * The output from the previous command.
     *
     * @var \Symfony\Component\Console\Output\OutputInterface
     */
    protected $lastOutput;

    /**
     * Create a new console application instance.
     *
     * @param  \Arpon\Foundation\Application  $laravel
     * @return void
     */
    public function __construct(FoundationApplication $laravel)
    {
        parent::__construct('Arpon MVC', $laravel->version());

        $this->laravel = $laravel;
        $this->setAutoExit(false);
        $this->setCatchExceptions(true);

        $this->bootstrap();
    }

    /**
     * Bootstrap the console application.
     *
     * @return void
     */
    protected function bootstrap()
    {
        // Load built-in commands from ConsoleServiceProvider
        $providers = $this->laravel->getServiceProviders();
        foreach ($providers as $provider) {
            if ($provider instanceof ConsoleServiceProvider) {
                $provider->addCommandsToConsole($this);
                break;
            }
        }
        
        // Load user commands from app/Console/Commands
        if (is_dir($this->laravel->path('app/Console/Commands'))) {
            $this->loadUserCommands($this->laravel->path('app/Console/Commands'));
        }
    }

    /**
     * Load user commands from a directory.
     *
     * @param  string  $path
     * @return void
     */
    protected function loadUserCommands($path)
    {
        $files = glob($path . '/*.php');
        
        foreach ($files as $file) {
            $className = 'App\\Console\\Commands\\' . basename($file, '.php');
            
            if (class_exists($className)) {
                $command = $this->laravel->make($className);
                
                if ($command instanceof Command) {
                    if (method_exists($command, 'setArponApplication')) {
                        $command->setArponApplication($this->laravel);
                    }
                    $this->add($command);
                }
            }
        }
    }

    /**
     * Run an Artisan console command by name.
     *
     * @param  string  $command
     * @param  array  $parameters
     * @param  \Symfony\Component\Console\Output\OutputInterface|null  $outputBuffer
     * @return int
     */
    public function call($command, array $parameters = [], $outputBuffer = null)
    {
        $command = $this->find($command);

        $input = new ArrayInput($parameters);
        $output = $outputBuffer ?: new BufferedOutput;

        $this->lastOutput = $output;

        return $command->run($input, $output);
    }

    /**
     * Get the output from the last command.
     *
     * @return string
     */
    public function output()
    {
        return $this->lastOutput ? $this->lastOutput->fetch() : '';
    }

    /**
     * {@inheritdoc}
     */
    protected function getDefaultInputDefinition(): \Symfony\Component\Console\Input\InputDefinition
    {
        return tap(parent::getDefaultInputDefinition(), function ($definition) {
            $definition->addOption($this->getEnvironmentOption());
        });
    }

    /**
     * Get the global environment option definition.
     *
     * @return \Symfony\Component\Console\Input\InputOption
     */
    protected function getEnvironmentOption()
    {
        $message = 'The environment the command should run in';

        return new InputOption('--env', null, InputOption::VALUE_OPTIONAL, $message);
    }
}
