<?php

namespace Arpon\Console\Commands;

use Arpon\Console\Command;

class ConfigClearCommand extends Command
{
    protected $name = 'config:clear';
    protected $description = 'Remove the configuration cache file';

    public function handle()
    {
        $cachePath = $this->app->bootstrapPath('cache/config.php');

        if (file_exists($cachePath)) {
            unlink($cachePath);
        }

        $this->info('Configuration cache cleared!');
        
        return 0;
    }
}
