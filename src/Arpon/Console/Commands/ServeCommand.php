<?php

namespace Arpon\Console\Commands;

use Arpon\Console\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;

class ServeCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'serve {--host=127.0.0.1 : The host to serve the application on} {--port=8000 : The port to serve the application on}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Serve the application on the PHP development server';

    /**
     * Execute the console command.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $host = $input->getOption('host');
        $port = $input->getOption('port');

        $output->writeln("<info>Starting Arpon development server:</info> http://{$host}:{$port}");
        $output->writeln("<info>Press Ctrl+C to stop the server</info>");

        $publicPath = $this->application->basePath('public');
        
        if (!is_dir($publicPath)) {
            $output->writeln("<error>Public directory not found: {$publicPath}</error>");
            return 1;
        }

        $publicPath = escapeshellarg($publicPath);
        $command = "php -S {$host}:{$port} -t {$publicPath}";

        passthru($command);

        return 0;
    }
}
