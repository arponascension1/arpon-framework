<?php

namespace Arpon\Foundation\Bootstrap;

use Arpon\Foundation\Application;

class BootProviders
{
    public function bootstrap(Application $app)
    {
        foreach ($app->getLoadedProviders() as $provider => $loaded) {
            if ($loaded) {
                $providerInstance = $app->getProvider($provider);
                if ($providerInstance && method_exists($providerInstance, 'boot')) {
                    $app->call([$providerInstance, 'boot']);
                }
            }
        }
    }
}
