<?php

namespace Arpon\Contracts\Auth;

interface TokenRepository
{
    /**
     * Create a new token record.
     *
     * @param  \Arpon\Contracts\Auth\CanResetPassword  $user
     * @return string
     */
    public function create(CanResetPassword $user);

    /**
     * Determine if a token record exists and is valid.
     *
     * @param  \Arpon\Contracts\Auth\CanResetPassword  $user
     * @param  string  $token
     * @return bool
     */
    public function exists(CanResetPassword $user, $token);

    /**
     * Determine if the given user recently created a password reset token.
     *
     * @param  \Arpon\Contracts\Auth\CanResetPassword  $user
     * @return bool
     */
    public function recentlyCreatedToken(CanResetPassword $user);

    /**
     * Delete a token record by user.
     *
     * @param  \Arpon\Contracts\Auth\CanResetPassword  $user
     * @return void
     */
    public function delete(CanResetPassword $user);

    /**
     * Delete expired tokens.
     *
     * @return void
     */
    public function deleteExpired();
}
