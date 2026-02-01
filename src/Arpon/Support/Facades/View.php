<?php

namespace Arpon\Support\Facades;

/**
 * @method static \Arpon\View\View make(string $view, array $data = [])
 * @method static string render(string $view, array $data = [])
 * @method static bool exists(string $view)
 * @method static \Arpon\View\Factory share(string|array $key, mixed $value = null)
 * @method static \Arpon\View\Factory composer(string|array $views, \Closure|string $callback)
 * @method static \Arpon\View\Factory creator(string|array $views, \Closure|string $callback)
 * @method static array getShared()
 *
 * @see \Arpon\View\Factory
 */
class View extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'view';
    }
}
