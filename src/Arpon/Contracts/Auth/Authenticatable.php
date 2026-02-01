<?php

namespace Arpon\Contracts\Auth;

interface Authenticatable
{
    /**
     * Get the unique identifier for the user.
     *
     * @return mixed
     */
    public function getAuthIdentifier(): mixed;

    /**
     * Get the password for the user.
     *
     * @return string
     */
    public function getAuthPassword(): string;

    /**
     * Get the token value for the "remember me" session.
     *
     * @return string|null
     */
    public function getRememberToken(): ?string;

    /**
     * Set the token value for the "remember me" session.
     *
     * @param string|null $value
     * @return void
     */
    public function setRememberToken(?string $value): void;

    /**
     * Get the column name for the "remember me" token.
     *
     * @return string
     */
    public function getRememberTokenName(): string;
}
