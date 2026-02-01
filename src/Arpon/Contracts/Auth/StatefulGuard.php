<?php

namespace Arpon\Contracts\Auth;

interface StatefulGuard extends Guard
{
    /**
     * Attempt to authenticate a user using the given credentials.
     *
     * @param  array  $credentials
     * @param  bool  $remember
     * @return bool
     */
    public function attempt(array $credentials = [], $remember = false);

    /**
     * Log a user into the application.
     *
     * @param  \Arpon\Contracts\Auth\Authenticatable  $user
     * @param  bool  $remember
     * @return void
     */
    public function login(Authenticatable $user, $remember = false);

    /**
     * Log the given user ID into the application.
     *
     * @param  mixed  $id
     * @param  bool  $remember
     * @return \Arpon\Contracts\Auth\Authenticatable|null
     */
    public function loginUsingId($id, $remember = false);

    /**
     * Log the user out of the application.
     *
     * @return void
     */
    public function logout();

    /**
     * Invalidate the current user's session.
     *
     * @return void
     */
    public function invalidate();

    /**
     * Register an authentication listener event.
     *
     * @param  string  $event
     * @param  \Closure  $callback
     * @return void
     */
    public function listen($event, $callback);

    /**
     * Determine if the user was authenticated via "remember me" cookie.
     *
     * @return bool
     */
    public function viaRemember();
}
