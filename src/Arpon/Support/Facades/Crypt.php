<?php

namespace Arpon\Support\Facades;

/**
 * @method static string encrypt(mixed $value, bool $serialize = true)
 * @method static mixed decrypt(string $payload, bool $unserialize = true)
 *
 * @see \Arpon\Encryption\Encrypter
 */
class Crypt extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'encrypter';
    }
}
