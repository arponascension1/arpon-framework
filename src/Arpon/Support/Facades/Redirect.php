<?php

namespace Arpon\Support\Facades;

/**
 * @method static \Arpon\Http\RedirectResponse to(string $path, int $status = 302, array $headers = [])
 * @method static \Arpon\Http\RedirectResponse route(string $name, array $parameters = [], int $status = 302, array $headers = [])
 * @method static \Arpon\Http\RedirectResponse back(int $status = 302, array $headers = [])
 * @method static \Arpon\Http\RedirectResponse away(string $path, int $status = 302, array $headers = [])
 * @method static \Arpon\Http\RedirectResponse with(string|array $key, mixed $value = null)
 * @method static \Arpon\Http\RedirectResponse withInput(array $input = null)
 * @method static \Arpon\Http\RedirectResponse withErrors(array $errors)
 *
 * @see \Arpon\Routing\Redirector
 */
class Redirect extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'redirect';
    }
}
