<?php

namespace Arpon\Console\Commands;

use Arpon\Console\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;

class MigrateRollbackCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'migrate:rollback {--step=1 : The number of migrations to rollback} {--force : Force the operation to run when in production}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Rollback the last database migration';

    /**
     * Execute the console command.
     *
     * @param  \Symfony\Component\Console\Input\InputInterface  $input
     * @param  \Symfony\Component\Console\Output\OutputInterface  $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $migrator = $this->application->make('migrator');
        
        $rolledBack = $migrator->rollback([$this->application->databasePath('migrations')]);

        foreach ($migrator->getNotes() as $note) {
            $output->writeln($note);
        }

        $output->writeln('<info>Migration rollback completed successfully.</info>');

        return 0;
    }
}
