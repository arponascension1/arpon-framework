<?php

namespace Arpon\Console\Commands;

use Arpon\Console\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MigrateStatusCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'migrate:status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Show the status of each migration';

    /**
     * Execute the console command.
     *
     * @param  \Symfony\Component\Console\Input\InputInterface  $input
     * @param  \Symfony\Component\Console\Output\OutputInterface  $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $repository = $this->application->make('migration.repository');
        $ran = $repository->getRan();

        if (count($ran) > 0) {
            $output->writeln('<info>Ran migrations:</info>');
            foreach ($ran as $migration) {
                $output->writeln('  <info>✓</info> ' . $migration);
            }
        } else {
            $output->writeln('<info>No migrations have been run.</info>');
        }

        return 0;
    }
}
