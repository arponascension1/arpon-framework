<?php

namespace Arpon\Console;

use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Application as ConsoleApplication;
use Arpon\Foundation\Application;

abstract class Command extends SymfonyCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature;

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description;

    /**
     * The Arpon application instance.
     *
     * @var Application|null
     */
    protected ?Application $application = null;

    /**
     * Alias for $application property for backward compatibility.
     *
     * @var Application|null
     */
    protected ?Application $app = null;

    /**
     * The name of the console command.
     *
     * @var string
     */
    protected $name;

    /**
     * The input interface implementation.
     *
     * @var \Symfony\Component\Console\Input\InputInterface
     */
    protected $input;

    /**
     * The output interface implementation.
     *
     * @var \Symfony\Component\Console\Output\OutputInterface
     */
    protected $output;

    /**
     * Create a new console command instance.
     *
     * @return void
     */
    public function __construct()
    {
        // Initialize with name if provided
        if (isset($this->name)) {
            parent::__construct($this->name);
            
            if (isset($this->description)) {
                $this->setDescription($this->description);
            }
        } else {
            parent::__construct();
        }

        if (isset($this->signature)) {
            $this->configureUsingFluentDefinition();
        }
    }

    /**
     * Configure the console command using a fluent definition.
     *
     * @return void
     */
    protected function configureUsingFluentDefinition()
    {
        [$name, $arguments, $options] = Parser::parse($this->signature);

        parent::__construct($this->name = $name);

        foreach ($arguments as $argument) {
            $this->getDefinition()->addArgument($argument);
        }

        foreach ($options as $option) {
            $this->getDefinition()->addOption($option);
        }
    }

    /**
     * Run the console command.
     *
     * @param  \Symfony\Component\Console\Input\InputInterface  $input
     * @param  \Symfony\Component\Console\Output\OutputInterface  $output
     * @return int
     */
    public function run(InputInterface $input, OutputInterface $output): int
    {
        $this->input = $input;
        $this->output = $output;

        return parent::run($input, $output);
    }

    /**
     * Execute the console command.
     *
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @return int
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $method = method_exists($this, 'handle') ? 'handle' : '__invoke';

        return (int) $this->application->call([$this, $method]);
    }

    /**
     * Set the Laravel application instance.
     *
     * @param Application|ConsoleApplication|null $app
     * @return void
     */
    public function setApplication(?ConsoleApplication $app = null): void
    {
        parent::setApplication($app);
    }
    
    /**
     * Set the Arpon application instance.
     *
     * @param Application $app
     * @return void
     */
    public function setArponApplication(Application $app): void
    {
        $this->application = $app;
        $this->app = $app; // Set alias for backward compatibility
    }

    /**
     * Call another console command.
     *
     * @param  string  $command
     * @param  array  $arguments
     * @return int
     */
    public function call($command, array $arguments = [])
    {
        return $this->getApplication()->call($command, $arguments);
    }


    /**
     * Write a string as information output.
     *
     * @param  string  $string
     * @param  int|string|null  $verbosity
     * @return void
     */
    public function info($string, $verbosity = null)
    {
        $this->line($string, 'info', $verbosity);
    }

    /**
     * Write a string as standard output.
     *
     * @param  string  $string
     * @param  string|null  $style
     * @param  int|string|null  $verbosity
     * @return void
     */
    public function line($string, $style = null, $verbosity = null)
    {
        $styled = $style ? "<$style>$string</$style>" : $string;

        $this->output->writeln($styled, $this->parseVerbosity($verbosity));
    }

    /**
     * Write a string as comment output.
     *
     * @param  string  $string
     * @param  int|string|null  $verbosity
     * @return void
     */
    public function comment($string, $verbosity = null)
    {
        $this->line($string, 'comment', $verbosity);
    }

    /**
     * Write a string as question output.
     *
     * @param  string  $string
     * @param  int|string|null  $verbosity
     * @return void
     */
    public function question($string, $verbosity = null)
    {
        $this->line($string, 'question', $verbosity);
    }

    /**
     * Write a string as error output.
     *
     * @param  string  $string
     * @param  int|string|null  $verbosity
     * @return void
     */
    public function error($string, $verbosity = null)
    {
        $this->line($string, 'error', $verbosity);
    }

    /**
     * Write a string as warning output.
     *
     * @param  string  $string
     * @param  int|string|null  $verbosity
     * @return void
     */
    public function warn($string, $verbosity = null)
    {
        if (! $style = $this->output->getFormatter()->hasStyle('warning')) {
            $style = new \Symfony\Component\Console\Formatter\OutputFormatterStyle('yellow');

            $this->output->getFormatter()->setStyle('warning', $style);
        }

        $this->line($string, 'warning', $verbosity);
    }

    /**
     * Get the verbosity level in terms of Symfony's OutputInterface level.
     *
     * @param  int|string|null  $level
     * @return int
     */
    protected function parseVerbosity($level = null)
    {
        if (isset($this->verbosityMap[$level])) {
            $level = $this->verbosityMap[$level];
        }

        return $level ?? OutputInterface::VERBOSITY_NORMAL;
    }
}