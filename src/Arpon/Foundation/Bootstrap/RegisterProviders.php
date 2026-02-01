<?php

namespace Arpon\Foundation\Bootstrap;

use Arpon\Foundation\Application;

class RegisterProviders
{
    public function bootstrap(Application $app)
    {
        $providersPath = $app->bootstrapPath('providers.php');

        if (!file_exists($providersPath)) {
            return;
        }

        $providers = require $providersPath;

        foreach ((array) $providers as $provider) {
            $app->register($provider);
        }
    }
}
