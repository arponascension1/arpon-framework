<?php

namespace Arpon\Auth;

use Arpon\Auth\Hashing\BcryptHasher;
use Arpon\Auth\Passwords\PasswordBroker;
use Arpon\Foundation\ServiceProvider;
use Arpon\Support\Str;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->registerAuthManager();
        $this->registerHasher();
        $this->registerPasswordBroker();
        $this->registerAccessGate();
    }

    /**
     * Register the access gate service.
     *
     * @return void
     */
    protected function registerAccessGate()
    {
        $this->app->singleton('gate', function ($app) {
            return new \Arpon\Auth\Access\Gate($app, function () use ($app) {
                return $app->make('auth')->user();
            });
        });
    }

    /**
     * Register the auth manager service.
     *
     * @return void
     */
    protected function registerAuthManager()
    {
        $this->app->singleton('auth', function ($app) {
            return new AuthManager($app);
        });

        $this->app->singleton('auth.driver', function ($app) {
            return $app['auth']->guard();
        });

        $this->app->alias('auth', AuthManager::class);
    }

    /**
     * Register the hasher service.
     *
     * @return void
     */
    protected function registerHasher()
    {
        $this->app->singleton('hash', function ($app) {
            return new BcryptHasher($app['config']->get('hashing', []));
        });

        $this->app->singleton('hash.driver', function ($app) {
            return $app['hash'];
        });


    }

    /**
     * Register the password broker instance.
     *
     * @return void
     */
    protected function registerPasswordBroker()
    {
        $this->registerTokenRepository();

        $this->app->singleton('auth.password', function ($app) {
            return new PasswordBroker(
                $app['auth.password.tokens'],
                $app['auth']->createUserProvider($app['config']->get('auth.defaults.provider', 'users')),
                $app['hash'],
                $app['events']
            );
        });


    }

    /**
     * Register the token repository implementation.
     *
     * @return void
     */
    protected function registerTokenRepository()
    {
        $this->app->singleton('auth.password.tokens', function ($app) {
            $config = $app['config']->get('auth.passwords.users');

            return new \Arpon\Auth\Passwords\DatabaseTokenRepository(
                $app['db']->connection($config['connection'] ?? null),
                $config['table'],
                $app['config']->get('app.key'),
                $config['expire'] ?? 60
            );
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerAuthEvents();
        $this->registerMacros();
    }

    /**
     * Register the authentication events.
     *
     * @return void
     */
    protected function registerAuthEvents()
    {
        if (! $this->app->bound('events')) {
            return;
        }

        $this->app['events']->listen('auth.attempting', function ($credentials, $remember) {
            // Event: authentication attempt
        });

        $this->app['events']->listen('auth.login', function ($user, $remember) {
            // Event: successful login
        });

        $this->app['events']->listen('auth.logout', function ($user) {
            // Event: logout
        });

        $this->app['events']->listen('auth.failed', function ($user, $credentials) {
            // Event: failed authentication
        });
    }

    /**
     * Register authentication macros.
     *
     * @return void
     */
    protected function registerMacros()
    {
        // Register any authentication-related macros here
    }
}
