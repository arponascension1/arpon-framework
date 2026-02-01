<?php

namespace Arpon\Support\Facades;

/**
 * @method static string to(string $path, array $parameters = [], bool $secure = null)
 * @method static string route(string $name, array $parameters = [], bool $absolute = true)
 * @method static string current()
 * @method static string full()
 * @method static string previous()
 * @method static bool isValidUrl(string $path)
 * @method static string asset(string $path, bool $secure = null)
 * @method static string secureAsset(string $path)
 *
 * @see \Arpon\Routing\UrlGenerator
 */
class URL extends Facade
{

    protected static function getFacadeAccessor()
    {
        return 'url';
    }
}
