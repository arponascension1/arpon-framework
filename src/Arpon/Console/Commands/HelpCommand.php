<?php

namespace Arpon\Console\Commands;

use Arpon\Console\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;

class HelpCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'help {command : The command to show help for}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Display help for a command';

    /**
     * Execute the console command.
     *
     * @param  \Symfony\Component\Console\Input\InputInterface  $input
     * @param  \Symfony\Component\Console\Output\OutputInterface  $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $commandName = $input->getArgument('command');

        if (!$commandName) {
            $this->getApplication()->renderException(new \InvalidArgumentException('Command name is required.'), $output);
            return 1;
        }

        $command = $this->getApplication()->find($commandName);

        $output->writeln('<info>Usage:</info>');
        $output->writeln('  php artisan ' . $command->getName());
        $output->writeln('');

        $output->writeln('<info>Description:</info>');
        $output->writeln('  ' . $command->getDescription());
        $output->writeln('');

        if ($command->getDefinition()->getArguments()) {
            $output->writeln('<info>Arguments:</info>');
            foreach ($command->getDefinition()->getArguments() as $argument) {
                $output->writeln('  ' . $argument->getName() . ' : ' . $argument->getDescription());
            }
            $output->writeln('');
        }

        if ($command->getDefinition()->getOptions()) {
            $output->writeln('<info>Options:</info>');
            foreach ($command->getDefinition()->getOptions() as $option) {
                $output->writeln('  --' . $option->getName() . ' : ' . $option->getDescription());
            }
            $output->writeln('');
        }

        return 0;
    }
}
