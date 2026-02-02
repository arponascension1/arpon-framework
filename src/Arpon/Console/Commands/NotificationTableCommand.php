<?php

namespace Arpon\Console\Commands;

use Arpon\Console\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class NotificationTableCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notification:table';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a migration for the notifications database table';

    /**
     * Execute the console command.
     *
     * @param  \Symfony\Component\Console\Input\InputInterface  $input
     * @param  \Symfony\Component\Console\Output\OutputInterface  $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $timestamp = date('Y_m_d_His');
        $className = 'create_notifications_table';
        $fileName = "{$timestamp}_{$className}.php";
        $filePath = $this->application->databasePath("migrations/{$fileName}");

        // Create directory if it doesn't exist
        $directory = dirname($filePath);
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        // Check if notification migration already exists
        $existingMigrations = glob($this->application->databasePath('migrations/*_create_notifications_table.php'));
        if (!empty($existingMigrations)) {
            $output->writeln('<comment>Notification migration already exists!</comment>');
            return 1;
        }

        // Load the notification stub
        $content = file_get_contents(__DIR__ . '/../stubs/notification.stub');

        // Write the file
        if (file_put_contents($filePath, $content) === false) {
            $output->writeln('<error>Failed to create notification migration file.</error>');
            return 1;
        }

        $output->writeln("<info>Notification migration created successfully:</info> {$filePath}");
        $output->writeln("\n<comment>Run 'php artisan migrate' to create the notifications table.</comment>");

        return 0;
    }
}
