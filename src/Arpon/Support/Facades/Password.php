<?php

namespace Arpon\Support\Facades;



/**
 * @method static string sendResetLink(array $credentials, bool $throttle = false)
 * @method static mixed reset(array $credentials, \Closure $callback)
 * @method static \Arpon\Contracts\Auth\CanResetPassword|null getUser(array $credentials)
 * @method static string createToken(\Arpon\Contracts\Auth\CanResetPassword $user)
 * @method static void deleteToken(\Arpon\Contracts\Auth\CanResetPassword $user)
 * @method static bool tokenExists(\Arpon\Contracts\Auth\CanResetPassword $user, string $token)
 * @method static \Arpon\Contracts\Auth\TokenRepository getRepository()
 *
 * @see \Arpon\Auth\Passwords\PasswordBroker
 */
class Password extends Facade
{
    /**
     * Constant representing a successfully sent password reset link.
     *
     * @var string
     */
    const RESET_LINK_SENT = 'passwords.sent';

    /**
     * Constant representing the user not found response.
     *
     * @var string
     */
    const INVALID_USER = 'passwords.user';

    /**
     * Constant representing the invalid token.
     *
     * @var string
     */
    const INVALID_TOKEN = 'passwords.token';

    /**
     * Constant representing the reset password being successful.
     *
     * @var string
     */
    const PASSWORD_RESET = 'passwords.reset';

    /**
     * Constant representing the user is throttled.
     *
     * @var string
     */
    const INVALID_THROTTLED = 'passwords.throttled';

    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'auth.password';
    }
}
