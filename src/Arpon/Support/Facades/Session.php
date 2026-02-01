<?php

namespace Arpon\Support\Facades;

/**
 * @method static void start()
 * @method static void save()
 * @method static mixed get(string $key, mixed $default = null)
 * @method static void put(string|array $key, mixed $value = null)
 * @method static void push(string $key, mixed $value)
 * @method static mixed pull(string $key, mixed $default = null)
 * @method static bool has(string $key)
 * @method static bool exists(string $key)
 * @method static void forget(string|array $keys)
 * @method static void flush()
 * @method static void regenerate(bool $destroy = false)
 * @method static void invalidate()
 * @method static string getId()
 * @method static void setId(string $id)
 * @method static bool isStarted()
 * @method static string token()
 * @method static void regenerateToken()
 *
 * @see \Arpon\Session\SessionManager
 * @see \Arpon\Session\Store
 */
class Session extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'session';
    }
}
