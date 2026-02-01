<?php

namespace Arpon\Support\Facades;

/**
 * @method static \Arpon\Filesystem\FilesystemAdapter disk(string $name = null)
 * @method static bool exists(string $path)
 * @method static string|false get(string $path)
 * @method static int|false put(string $path, $contents, array $options = [])
 * @method static string|false putFile(string $path, $file, string $name = null)
 * @method static string|false putFileAs(string $path, $file, string $name)
 * @method static bool delete(string|array $paths)
 * @method static bool copy(string $from, string $to)
 * @method static bool move(string $from, string $to)
 * @method static int|false size(string $path)
 * @method static string|false mimeType(string $path)
 * @method static int|false lastModified(string $path)
 * @method static array files(string $directory = '', bool $recursive = false)
 * @method static array directories(string $directory = '', bool $recursive = false)
 * @method static bool makeDirectory(string $path)
 * @method static bool deleteDirectory(string $directory)
 * @method static string url(string $path)
 * @method static string path(string $path = '')
 *
 * @see \Arpon\Filesystem\FilesystemManager
 */
class Storage extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'filesystem';
    }
}
