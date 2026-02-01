<?php

namespace Arpon\Support\Facades;

/**
 * @method static \Arpon\Contracts\Auth\Guard guard(string|null $name = null)
 * @method static \Arpon\Contracts\Auth\Guard getDefaultDriver()
 * @method static void setDefaultDriver(string $name)
 * @method static bool check()
 * @method static bool guest()
 * @method static \App\Models\User|\Arpon\Contracts\Auth\Authenticatable|null user()
 * @method static mixed id()
 * @method static bool validate(array $credentials = [])
 * @method static bool attempt(array $credentials = [], bool $remember = false)
 * @method static void login(\Arpon\Contracts\Auth\Authenticatable $user, bool $remember = false)
 * @method static \Arpon\Contracts\Auth\Authenticatable|null loginUsingId(mixed $id, bool $remember = false)
 * @method static bool once(array $credentials = [])
 * @method static \Arpon\Contracts\Auth\Authenticatable|null onceUsingId(mixed $id)
 * @method static void logout()
 * @method static \Arpon\Contracts\Auth\UserProvider createUserProvider(string|null $provider = null)
 * @method static \Arpon\Auth\AuthManager extend(string $driver, \Closure $callback)
 * @method static \Arpon\Auth\AuthManager provider(string $name, \Closure $callback)
 * @method static \Closure|null getUserResolver()
 * @method static \Arpon\Auth\AuthManager resolveUsersUsing(\Closure $userResolver)
 * @method static bool viaRemember()
 *
 * @see \Arpon\Auth\AuthManager
 */
class Auth extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'auth';
    }
}
