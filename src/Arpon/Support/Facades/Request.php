<?php

namespace Arpon\Support\Facades;

/**
 * @method static \Arpon\Http\Request capture()
 * @method static \Arpon\Http\Request create(string $uri, string $method = 'GET', array $parameters = [], array $cookies = [], array $files = [], array $server = [], string $content = null)
 * @method static mixed input(string $key = null, mixed $default = null)
 * @method static mixed query(string $key = null, mixed $default = null)
 * @method static mixed post(string $key = null, mixed $default = null)
 * @method static bool has(string $key)
 * @method static bool filled(string $key)
 * @method static array all()
 * @method static array only(array|mixed $keys)
 * @method static array except(array|mixed $keys)
 * @method static mixed cookie(string $key, mixed $default = null)
 * @method static array cookies()
 * @method static mixed header(string $key = null, mixed $default = null)
 * @method static bool hasHeader(string $key)
 * @method static string|null bearerToken()
 * @method static string method()
 * @method static string path()
 * @method static string url()
 * @method static string fullUrl()
 * @method static bool isMethod(string $method)
 * @method static bool is(...$patterns)
 * @method static string ip()
 * @method static array validate(array $rules, array $messages = [])
 *
 * @see \Arpon\Http\Request
 */
class Request extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'request';
    }

    /**
     * Handle dynamic, static calls to the object.
     *
     * @param  string  $method
     * @param  array  $args
     * @return mixed
     *
     * @throws \RuntimeException|\Exception
     */
    public static function __callStatic($method, $args)
    {
        if ($method === 'create') {
            return \Arpon\Http\Request::create(...$args);
        }

        if ($method === 'capture') {
            return \Arpon\Http\Request::capture(...$args);
        }

        return parent::__callStatic($method, $args);
    }
}
