<?php

namespace Arpon\Console\Commands;

use Arpon\Console\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MigrateFreshCommand extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'migrate:fresh {--force : Force the operation to run when in production}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Drop all tables and re-run all migrations';

    /**
     * Execute the console command.
     *
     * @param  \Symfony\Component\Console\Input\InputInterface  $input
     * @param  \Symfony\Component\Console\Output\OutputInterface  $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (!$this->confirmToProceed('Application is in production. This will drop all tables! Continue?', $input, $output)) {
            $output->writeln('<comment>Command cancelled.</comment>');
            return 1;
        }

        $db = $this->application['db'];
        $connection = $db->connection();
        $schema = $connection->getSchemaBuilder();
        
        $output->writeln('<comment>Dropping all tables...</comment>');
        
        // Get all tables
        $tables = $schema->getAllTables();
        
        // Drop all tables
        foreach ($tables as $tableName) {
            $output->writeln("<info>Dropped:</info> {$tableName}");
            $schema->dropIfExists($tableName);
        }
        
        $output->writeln('<info>All tables dropped successfully.</info>');
        
        // Run migrations
        $output->writeln('');
        $output->writeln('<comment>Running migrations...</comment>');
        
        $kernel = $this->application->make(\Arpon\Console\Kernel::class);
        $result = $kernel->call('migrate', [], $output);
        
        return $result;
    }
    
    /**
     * Confirm before proceeding with the action.
     *
     * @param  string  $warning
     * @param  \Symfony\Component\Console\Input\InputInterface  $input
     * @param  \Symfony\Component\Console\Output\OutputInterface  $output
     * @return bool
     */
    protected function confirmToProceed($warning, $input, $output)
    {
        if ($this->application['config']->get('app.env', 'production') === 'production') {
            if ($input->hasOption('force') && $input->getOption('force')) {
                return true;
            }

            $helper = $this->getHelper('question');
            $question = new \Symfony\Component\Console\Question\ConfirmationQuestion(
                "<question>{$warning} (yes/no)</question> ",
                false
            );

            return $helper->ask($input, $output, $question);
        }

        return true;
    }
}
