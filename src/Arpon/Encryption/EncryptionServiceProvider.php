<?php

namespace Arpon\Encryption;

use Arpon\Foundation\ServiceProvider;
use Arpon\Support\Str;

class EncryptionServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('encrypter', function ($app) {
            $config = $app->make('config')->get('app');

            $key = $config['key'];

            if (str_starts_with($key, 'base64:')) {
                $key = base64_decode(substr($key, 7));
            }

            return new Encrypter($key, $config['cipher'] ?? 'AES-256-CBC');
        });

        $this->app->alias('encrypter', Encrypter::class);
    }
}
