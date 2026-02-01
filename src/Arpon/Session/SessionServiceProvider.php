<?php

namespace Arpon\Session;

use Arpon\Foundation\ServiceProvider;
use Arpon\Cookie\CookieJar;
use Arpon\Routing\Redirector;
use Arpon\Routing\UrlGenerator;

class SessionServiceProvider extends ServiceProvider
{
    public function register()
    {
        // Register session manager
        $this->app->singleton('session', function ($app) {
            $config = $app->make('config')->get('session') ?: [];
            
            return new SessionManager($app, $config);
        });

        $this->app->alias('session', SessionManager::class);

        // Register cookie jar
        $this->app->singleton('cookie', function ($app) {
            return new CookieJar();
        });

        $this->app->alias('cookie', CookieJar::class);

        // Register URL generator
        $this->app->singleton('url', function ($app) {
            // Directly capture request to avoid facade issues
            $request = \Arpon\Http\Request::capture();
            $url = new UrlGenerator($request);

            if ($appUrl = $app['config']->get('app.url')) {
                $url->forceRootUrl($appUrl);
            }

            return $url;
        });

        // Register redirector
        $this->app->singleton('redirect', function ($app) {
            $urlGenerator = $app['url'];
            return new Redirector($urlGenerator);
        });
    }
}
