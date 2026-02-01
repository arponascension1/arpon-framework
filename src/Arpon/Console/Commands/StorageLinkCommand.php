<?php

namespace Arpon\Console\Commands;

use Arpon\Console\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;

class StorageLinkCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'storage:link {--relative : Create the symbolic link using relative paths} {--force : Recreate existing symbolic links}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create the symbolic link from public/storage to storage/app/public';

    /**
     * Execute the console command.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $relative = $input->getOption('relative');
        $force = $input->getOption('force');

        $links = $this->links();

        foreach ($links as $link => $target) {
            if (file_exists($link) && !$force) {
                $output->writeln("<error>The [{$link}] link already exists.</error>");
                continue;
            }

            if (is_link($link)) {
                unlink($link);
            }

            if ($relative) {
                $this->application->make('files')->relativeLink($target, $link);
            } else {
                $this->application->make('files')->link($target, $link);
            }

            $output->writeln("<info>The [{$link}] link has been connected to [{$target}].</info>");
        }

        $output->writeln('<info>The links have been created.</info>');

        return 0;
    }

    /**
     * Get the symbolic links that are configured for the application.
     *
     * @return array
     */
    protected function links()
    {
        return $this->application->make('config')->get('filesystems.links', [
            $this->application->publicPath('storage') => $this->application->storagePath('app/public'),
        ]);
    }
}
