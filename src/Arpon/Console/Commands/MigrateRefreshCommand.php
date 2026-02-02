<?php

namespace Arpon\Console\Commands;

use Arpon\Console\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MigrateRefreshCommand extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'migrate:refresh {--force : Force the operation to run when in production}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reset and re-run all migrations';

    /**
     * Execute the console command.
     *
     * @param  \Symfony\Component\Console\Input\InputInterface  $input
     * @param  \Symfony\Component\Console\Output\OutputInterface  $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (!$this->confirmToProceed('Application is in production. This will reset all migrations! Continue?', $input, $output)) {
            $output->writeln('<comment>Command cancelled.</comment>');
            return 1;
        }

        // Run reset
        $output->writeln('<comment>Rolling back all migrations...</comment>');
        
        $kernel = $this->application->make(\Arpon\Console\Kernel::class);
        $result = $kernel->call('migrate:reset', [], $output);
        
        if ($result !== 0) {
            return $result;
        }
        
        $output->writeln('');
        
        // Run migrations
        $output->writeln('<comment>Running migrations...</comment>');
        $result = $kernel->call('migrate', [], $output);
        
        return $result;
    }
}
