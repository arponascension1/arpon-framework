<?php

namespace Arpon\Auth;

use Arpon\Foundation\Application;

/**
 * @method \Arpon\Contracts\Auth\Authenticatable|null user()
 * @method bool check()
 * @method bool guest()
 * @method int|string|null id()
 * @method bool validate(array $credentials = [])
 * @method bool attempt(array $credentials = [], bool $remember = false)
 * @method bool once(array $credentials = [])
 * @method void login(\Arpon\Contracts\Auth\Authenticatable $user, bool $remember = false)
 * @method void loginUsingId(int|string $id, bool $remember = false)
 * @method bool viaRemember()
 * @method void logout()
 * 
 * @see \Arpon\Auth\SessionGuard
 */
class AuthManager
{
    /**
     * The application instance.
     *
     * @var \Arpon\Foundation\Application
     */
    protected $app;

    /**
     * The registered custom driver creators.
     *
     * @var array
     */
    protected $customCreators = [];

    /**
     * The registered custom provider creators.
     *
     * @var array
     */
    protected $customProviderCreators = [];

    /**
     * The array of created "drivers".
     *
     * @var array
     */
    protected $guards = [];

    /**
     * The user resolver shared by various services.
     *
     * Determines the default user for Guard, Request, and TokenGuard.
     *
     * @var \Closure|null
     */
    protected $userResolver;

    /**
     * Create a new authentication manager instance.
     *
     * @param  \Arpon\Foundation\Application  $app
     * @return void
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Get the default authentication driver name.
     *
     * @return string
     */
    public function getDefaultDriver()
    {
        return $this->app['config']->get('auth.defaults.guard', 'session');
    }

    /**
     * Set the default authentication driver name.
     *
     * @param  string  $name
     * @return void
     */
    public function setDefaultDriver($name)
    {
        $this->app['config']->set('auth.defaults.guard', $name);
    }

    /**
     * Get a guard instance by name.
     *
     * @param  string|null  $name
     * @return \Arpon\Contracts\Auth\Guard
     */
    public function guard($name = null)
    {
        $name = $name ?: $this->getDefaultDriver();

        if (!isset($this->guards[$name])) {
            $this->guards[$name] = $this->resolve($name);
        }

        return $this->guards[$name];
    }

    /**
     * Resolve the given guard.
     *
     * @param  string  $name
     * @return \Arpon\Contracts\Auth\Guard
     */
    protected function resolve($name)
    {
        $config = $this->getConfig($name);

        if (is_null($config)) {
            throw new \InvalidArgumentException("Auth guard [{$name}] is not defined.");
        }

        if (isset($this->customCreators[$config['driver']])) {
            return $this->callCustomCreator($name, $config);
        }

        $driverMethod = 'create'.ucfirst($config['driver']).'Driver';

        if (method_exists($this, $driverMethod)) {
            return $this->{$driverMethod}($name, $config);
        }

        throw new \InvalidArgumentException(
            "Auth driver [{$config['driver']}] for guard [{$name}] is not supported."
        );
    }

    /**
     * Call a custom driver creator.
     *
     * @param  string  $name
     * @param  array  $config
     * @return mixed
     */
    protected function callCustomCreator($name, array $config)
    {
        return $this->customCreators[$config['driver']]($this->app, $name, $config);
    }

    /**
     * Create a session based authentication guard.
     *
     * @param  string  $name
     * @param  array  $config
     * @return \Arpon\Auth\SessionGuard
     */
    protected function createSessionDriver($name, $config)
    {
        $provider = $this->createUserProvider($config['provider'] ?? null);

        $guard = new SessionGuard($name, $provider, $this->app['session']->getStore());

        // When using the remember me functionality we will set a cookie to be sent
        // with the response. This cookie will be used to identify the user on the
        // next request and will authenticate them via the "remember me" token.
        if (method_exists($guard, 'setCookieJar')) {
            $guard->setCookieJar($this->app['cookie']);
        }

        if (method_exists($guard, 'setDispatcher')) {
            $guard->setDispatcher($this->app['events']);
        }

        if (method_exists($guard, 'setRequest')) {
            $guard->setRequest($this->app->make('request'));
        }

        return $guard;
    }

    /**
     * Create a token based authentication guard.
     *
     * @param  string  $name
     * @param  array  $config
     * @return \Arpon\Auth\TokenGuard
     */
    protected function createTokenDriver($name, $config)
    {
        $provider = $this->createUserProvider($config['provider'] ?? null);

        return new TokenGuard(
            $provider, 
            $this->app['request'], 
            $config['input_key'] ?? 'api_token',
            $config['storage_key'] ?? 'api_token',
            $config['hash'] ?? false
        );
    }

    /**
     * Create the user provider for a given driver.
     *
     * @param  string|null  $provider
     * @return \Arpon\Contracts\Auth\UserProvider|null
     */
    public function createUserProvider($provider = null)
    {
        if (is_null($provider)) {
            return $this->createDefaultUserProvider();
        }

        $config = $this->app['config']->get('auth.providers.'.$provider);

        if (is_null($config)) {
            throw new \InvalidArgumentException("Authentication provider [{$provider}] is not defined.");
        }

        if (isset($this->customProviderCreators[$driver = ($config['driver'] ?? null)])) {
            return $this->customProviderCreators[$driver]($this->app, $config);
        }

        $driverMethod = 'create'.ucfirst($driver).'UserProvider';

        if (method_exists($this, $driverMethod)) {
            return $this->{$driverMethod}($this->app, $config);
        }

        throw new \InvalidArgumentException(
            "Authentication user provider [{$driver}] is not supported."
        );
    }

    /**
     * Create the default user provider.
     *
     * @return \Arpon\Contracts\Auth\UserProvider
     */
    protected function createDefaultUserProvider()
    {
        $provider = $this->app['config']->get('auth.defaults.provider', 'users');

        return $this->createUserProvider($provider);
    }

    /**
     * Create an Eloquent user provider.
     *
     * @param  \Arpon\Foundation\Application  $app
     * @param  array  $config
     * @return \Arpon\Auth\EloquentUserProvider
     */
    protected function createEloquentUserProvider($app, array $config)
    {
        return new EloquentUserProvider($app['hash'], $config['model']);
    }

    /**
     * Get the guard configuration.
     *
     * @param  string  $name
     * @return array|null
     */
    protected function getConfig($name)
    {
        return $this->app['config']->get("auth.guards.{$name}");
    }

    /**
     * Register a custom driver creator Closure.
     *
     * @param  string  $driver
     * @param  \Closure  $callback
     * @return $this
     */
    public function extend($driver, \Closure $callback)
    {
        $this->customCreators[$driver] = $callback;

        return $this;
    }

    /**
     * Register a custom provider creator Closure.
     *
     * @param  string  $name
     * @param  \Closure  $callback
     * @return $this
     */
    public function provider($name, \Closure $callback)
    {
        $this->customProviderCreators[$name] = $callback;

        return $this;
    }

    /**
     * Get the user resolver callback.
     *
     * @return \Closure|null
     */
    public function getUserResolver()
    {
        return $this->userResolver;
    }

    /**
     * Set the callback to be used to resolve users.
     *
     * @param  \Closure  $userResolver
     * @return $this
     */
    public function resolveUsersUsing(\Closure $userResolver)
    {
        $this->userResolver = $userResolver;

        return $this;
    }

    /**
     * Set the default guard the factory should serve.
     *
     * @param  string  $name
     * @return void
     */
    public function shouldUse($name)
    {
        $name = $name ?: $this->getDefaultDriver();

        $this->setDefaultDriver($name);

        $this->userResolver = function ($guard = null) use ($name) {
            return $this->guard($guard ?: $name)->user();
        };
    }

    /**
     * Dynamically call the default driver instance.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->guard()->{$method}(...$parameters);
    }
}
