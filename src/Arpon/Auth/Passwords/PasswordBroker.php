<?php

namespace Arpon\Auth\Passwords;

use Arpon\Contracts\Hashing\Hasher;
use Arpon\Contracts\Auth\UserProvider;
use Arpon\Contracts\Auth\TokenRepository;
use Arpon\Contracts\Auth\CanResetPassword;
use Arpon\Events\Dispatcher;
use Closure;

class PasswordBroker
{
    /**
     * Constant representing a successfully sent reminder.
     */
    const RESET_LINK_SENT = 'passwords.sent';

    /**
     * Constant representing a successfully reset password.
     */
    const PASSWORD_RESET = 'passwords.reset';

    /**
     * Constant representing the user not found response.
     */
    const INVALID_USER = 'passwords.user';

    /**
     * Constant representing an invalid token.
     */
    const INVALID_TOKEN = 'passwords.token';

    /**
     * Constant representing a throttled reset attempt.
     */
    const RESET_THROTTLED = 'passwords.throttled';

    /**
     * The password token repository instance.
     *
     * @var \Arpon\Contracts\Auth\TokenRepository
     */
    protected $tokens;

    /**
     * The user provider instance.
     *
     * @var \Arpon\Contracts\Auth\UserProvider
     */
    protected $users;

    /**
     * The hasher instance.
     *
     * @var \Arpon\Contracts\Hashing\Hasher
     */
    protected $hasher;

    /**
     * The event dispatcher instance.
     *
     * @var \Arpon\Events\Dispatcher
     */
    protected $events;

    /**
     * Create a new password broker instance.
     *
     * @param  \Arpon\Contracts\Auth\TokenRepository  $tokens
     * @param  \Arpon\Contracts\Auth\UserProvider  $users
     * @param  \Arpon\Contracts\Hashing\Hasher  $hasher
     * @param  \Arpon\Events\Dispatcher|null  $events
     * @return void
     */
    public function __construct(TokenRepository $tokens, UserProvider $users, Hasher $hasher, Dispatcher $events = null)
    {
        $this->tokens = $tokens;
        $this->users = $users;
        $this->hasher = $hasher;
        $this->events = $events;
    }

    /**
     * Send a password reset link to a user.
     *
     * @param  array  $credentials
     * @param  bool   $throttle
     * @return string
     */
    public function sendResetLink(array $credentials, bool $throttle = false)
    {
        $user = $this->getUser($credentials);

        if (is_null($user)) {
            return static::INVALID_USER;
        }

        if ($throttle && $this->tokens->recentlyCreatedToken($user)) {
            return static::RESET_THROTTLED;
        }

        $token = $this->tokens->create($user);

        if ($this->events) {
            $this->events->dispatch('password.reset', [$user, $token]);
        }

        $user->sendPasswordResetNotification($token);

        return static::RESET_LINK_SENT;
    }
        
            /**
             * Reset the password for the given token.
             *
             * @param  array  $credentials
             * @param  \Closure  $callback
             * @return mixed
             */
            public function reset(array $credentials, Closure $callback)
            {
                $user = $this->validateReset($credentials);
        
                if (! $user || !method_exists($user, 'getEmailForPasswordReset')) {
                    return static::INVALID_USER;
                }
        
                $password = $credentials['password'];
        
                $callback($user, $password);
        
                $this->tokens->delete($user);
        
                return static::PASSWORD_RESET;
            }
        
            /**
             * Validate a password reset for the given credentials.
             *
             * @param  array  $credentials
             * @return CanResetPassword|string
             */
            protected function validateReset(array $credentials)
            {
                if (is_null($user = $this->getUser($credentials))) {
                    return static::INVALID_USER;
                }
        
                if (! $this->tokens->exists($user, $credentials['token'])) {
                    return static::INVALID_TOKEN;
                }
        
                return $user;
            }
        
            /**
             * Get the user for the given credentials.
             *
             * @param  array  $credentials
             * @return CanResetPassword|null
             */
                public function getUser(array $credentials)
                {
                    $credentials = array_filter($credentials, function ($key) {
                        return ! in_array($key, ['token', 'password', 'password_confirmation']);
                    }, ARRAY_FILTER_USE_KEY);
            
                    $user = $this->users->retrieveByCredentials($credentials);
            
                    if ($user && !method_exists($user, 'getEmailForPasswordReset')) {
                        return null;
                    }
            
                    return $user;
                }    /**
     * Create a new password reset token for the given user.
     *
     * @param CanResetPassword $user
     * @return string
     */
    public function createToken(object $user)
    {
        return $this->tokens->create($user);
    }

    /**
     * Delete password reset tokens of the given user.
     *
     * @param CanResetPassword $user
     * @return void
     */
    public function deleteToken(object $user)
    {
        $this->tokens->delete($user);
    }

    /**
     * Validate the given password reset token.
     *
     * @param CanResetPassword $user
     * @param  string  $token
     * @return bool
     */
    public function tokenExists(object $user, $token)
    {
        return $this->tokens->exists($user, $token);
    }

    /**
     * Get the password reset token repository instance.
     *
     * @return \Arpon\Contracts\Auth\TokenRepository
     */
    public function getRepository()
    {
        return $this->tokens;
    }
}
