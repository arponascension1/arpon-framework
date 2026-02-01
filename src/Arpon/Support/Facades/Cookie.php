<?php

namespace Arpon\Support\Facades;

/**
 * @method static \Arpon\Cookie\Cookie make(string $name, string $value, int $minutes = 0, string $path = null, string $domain = null, bool $secure = null, bool $httpOnly = true, string $sameSite = null)
 * @method static \Arpon\Cookie\Cookie forever(string $name, string $value, string $path = null, string $domain = null, bool $secure = null, bool $httpOnly = true, string $sameSite = null)
 * @method static \Arpon\Cookie\Cookie forget(string $name, string $path = null, string $domain = null)
 * @method static bool hasQueued(string $name)
 * @method static \Arpon\Cookie\Cookie|null queued(string $name, mixed $default = null)
 * @method static void queue(\Arpon\Cookie\Cookie $cookie)
 * @method static void unqueue(string $name)
 * @method static array getQueuedCookies()
 * @method static void sendQueuedCookies()
 *
 * @see \Arpon\Cookie\CookieJar
 */
class Cookie extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'cookie';
    }
}
