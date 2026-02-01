<?php

namespace Arpon\Console\Commands;

use Arpon\Console\Command;

class ConfigCacheCommand extends Command
{
    protected $name = 'config:cache';
    protected $description = 'Create a cache file for faster configuration loading';

    public function handle()
    {
        $this->call('config:clear');

        $config = $this->app->make('config')->all();

        $cachePath = $this->app->bootstrapPath('cache/config.php');

        if (!is_dir(dirname($cachePath))) {
            mkdir(dirname($cachePath), 0755, true);
        }

        file_put_contents(
            $cachePath,
            '<?php return '.var_export($config, true).';'.PHP_EOL
        );

        $this->info('Configuration cached successfully!');
        
        return 0;
    }
}
