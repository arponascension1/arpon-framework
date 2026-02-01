<?php

namespace Arpon\Console\Commands;

use Arpon\Console\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;

class KeyGenerateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'key:generate {--show : Display the key instead of modifying files}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Set the application key';

    /**
     * Execute the console command.
     *
     * @param  \Symfony\Component\Console\Input\InputInterface  $input
     * @param  \Symfony\Component\Console\Output\OutputInterface  $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $key = $this->generateRandomKey();
        
        if ($input->getOption('show')) {
            $output->writeln('<comment>'.$key.'</comment>');
            return 0;
        }

        if (!$this->setKeyInEnvironmentFile($key)) {
            $output->writeln('<error>Unable to set application key. No .env file found.</error>');
            return 1;
        }

        $this->application['config']['app.key'] = $key;

        $output->writeln("<info>Application key set successfully.</info> [{$key}]");

        return 0;
    }

    /**
     * Generate a random key for the application.
     *
     * @return string
     */
    protected function generateRandomKey()
    {
        return 'base64:' . base64_encode(random_bytes(32));
    }

    /**
     * Set the application key in the environment file.
     *
     * @param  string  $key
     * @return bool
     */
    protected function setKeyInEnvironmentFile($key)
    {
        $envPath = $this->application->basePath('.env');

        if (!file_exists($envPath)) {
            return false;
        }

        $content = file_get_contents($envPath);
        $oldKey = (string) ($this->application['config']['app.key'] ?? '');

        if ($oldKey !== '' && str_contains($content, "APP_KEY={$oldKey}")) {
            $content = str_replace("APP_KEY={$oldKey}", "APP_KEY={$key}", $content);
        } elseif (preg_match('/^APP_KEY=/m', $content)) {
            $content = preg_replace('/^APP_KEY=[^\r\n]*/m', "APP_KEY={$key}", $content);
        } else {
            $content .= (strlen($content) > 0 && str_ends_with($content, "\n") ? "" : "\n") . "APP_KEY={$key}\n";
        }

        return file_put_contents($envPath, $content) !== false;
    }
}
