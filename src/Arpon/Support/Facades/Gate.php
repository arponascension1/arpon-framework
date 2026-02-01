<?php

namespace Arpon\Support\Facades;

/**
 * @method static bool has(string $ability)
 * @method static \Arpon\Auth\Access\Gate define(string $ability, callable|string $callback)
 * @method static \Arpon\Auth\Access\Gate before(callable $callback)
 * @method static bool allows(string $ability, mixed $arguments = [])
 * @method static bool denies(string $ability, mixed $arguments = [])
 * @method static bool check(string $ability, mixed $arguments = [])
 * @method static bool authorize(string $ability, mixed $arguments = [])
 *
 * @see \Arpon\Auth\Access\Gate
 */
class Gate extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return 'gate';
    }
}
