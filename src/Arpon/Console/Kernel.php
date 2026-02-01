<?php

namespace Arpon\Console;

use Arpon\Foundation\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\ConsoleOutput;

class Kernel
{
    /**
     * The application instance.
     *
     * @var \Arpon\Foundation\Application
     */
    protected $app;

    /**
     * The Artisan application instance.
     *
     * @var \Arpon\Console\Application
     */
    protected $artisan;

    /**
     * The Artisan commands provided by the application.
     *
     * @var array
     */
    protected $commands = [];

    /**
     * Create a new console kernel instance.
     *
     * @param  \Arpon\Foundation\Application  $app
     * @return void
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Run the console application.
     *
     * @param  \Symfony\Component\Console\Input\InputInterface  $input
     * @param  \Symfony\Component\Console\Output\OutputInterface|null  $output
     * @return int
     */
    public function handle($input, $output = null)
    {
        try {
            $this->bootstrap();

            return $this->artisan()->run($input, $output);
        } catch (\Throwable $e) {
            $this->reportException($e);

            $this->renderException($output, $e);

            return 1;
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
        $this->bootstrap();

        return $this->artisan()->call($command, $parameters, $outputBuffer);
    }

    /**
     * Get the Artisan application instance.
     *
     * @return \Arpon\Console\Application
     */
    protected function artisan()
    {
        if (is_null($this->artisan)) {
            $this->artisan = new \Arpon\Console\Application($this->app);
        }

        return $this->artisan;
    }

    /**
     * Bootstrap the console application.
     *
     * @return void
     */
    public function bootstrap()
    {
        if (!$this->app->isBooted()) {
            $this->app->bootstrapWith([
                \Arpon\Foundation\Bootstrap\LoadEnvironmentVariables::class,
                \Arpon\Foundation\Bootstrap\LoadConfiguration::class,
                \Arpon\Foundation\Bootstrap\RegisterProviders::class,
                \Arpon\Foundation\Bootstrap\BootProviders::class,
            ]);
        }
    }

    /**
     * Report the exception to the exception handler.
     *
     * @param  \Throwable  $e
     * @return void
     */
    protected function reportException(\Throwable $e)
    {
        // For now, just log the exception
        error_log($e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine());
    }

    /**
     * Render an exception to the console.
     *
     * @param  \Symfony\Component\Console\Output\OutputInterface  $output
     * @param  \Throwable  $e
     * @return void
     */
    protected function renderException($output, \Throwable $e)
    {
        if ($output instanceof \Symfony\Component\Console\Output\ConsoleOutputInterface) {
            $output = $output->getErrorOutput();
        }

        $output->writeln('<error>' . $e->getMessage() . '</error>');
        $output->writeln('<error>in ' . $e->getFile() . ' on line ' . $e->getLine() . '</error>');
        
        $debug = $this->app->config->get('app.debug', false);
        
        if ($debug) {
            $output->writeln('<error>Stack trace:</error>');
            $output->writeln('<error>' . $e->getTraceAsString() . '</error>');
        }
    }

    /**
     * Terminate the application.
     *
     * @param  \Symfony\Component\Console\Input\InputInterface  $input
     * @param  int  $status
     * @return void
     */
    public function terminate($input, $status)
    {
        //
    }
}
