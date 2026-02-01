<?php

namespace Arpon\Cache;

use Arpon\Contracts\Cache\Store;
use Arpon\Support\Filesystem;
use Exception;

class FileStore implements Store
{
    /**
     * The Filesystem instance.
     *
     * @var \Arpon\Support\Filesystem
     */
    protected $files;

    /**
     * The file cache directory.
     *
     * @var string
     */
    protected $directory;

    /**
     * The file permissions.
     *
     * @var int|null
     */
    protected $permission;

    /**
     * Create a new file cache store instance.
     *
     * @param  \Arpon\Support\Filesystem  $files
     * @param  string  $directory
     * @param  int|null  $permission
     * @return void
     */
    public function __construct(Filesystem $files, $directory, $permission = null)
    {
        $this->files = $files;
        $this->directory = $directory;
        $this->permission = $permission;
    }

    /**
     * Retrieve an item from the cache by key.
     *
     * @param  string|array  $key
     * @return mixed
     */
    public function get($key)
    {
        return $this->getPayload($key)['data'] ?? null;
    }

    /**
     * Retrieve multiple items from the cache by key.
     *
     * Items not found in the cache will have a null value.
     *
     * @param  array  $keys
     * @return array
     */
    public function many(array $keys)
    {
        $return = [];

        foreach ($keys as $key) {
            $return[$key] = $this->get($key);
        }

        return $return;
    }

    /**
     * Store an item in the cache for a given number of seconds.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @param  int  $seconds
     * @return bool
     */
    public function put($key, $value, $seconds)
    {
        $this->ensureCacheDirectoryExists($path = $this->path($key));

        $result = $this->files->put(
            $path, $this->expiration($seconds).serialize($value), true
        );

        if ($result !== false && $result > 0) {
            $this->ensurePermissions($path);

            return true;
        }

        return false;
    }

    /**
     * Store multiple items in the cache for a given number of seconds.
     *
     * @param  array  $values
     * @param  int  $seconds
     * @return bool
     */
    public function putMany(array $values, $seconds)
    {
        foreach ($values as $key => $value) {
            $this->put((string) $key, $value, $seconds);
        }

        return true;
    }

    /**
     * Increment the value of an item in the cache.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return int|bool
     */
    public function increment($key, $value = 1)
    {
        $raw = $this->getPayload($key);

        if (is_null($raw)) {
            $this->forever($key, $value);
            return $value;
        }
        
        $current = $raw['data'];
        $new = ((int) $current) + ((int) $value);
        
        // We must calculate remaining seconds for put()
        // But wait, put() takes seconds from NOW.
        // We need to preserve the original absolute expiration.
        // But Store interface put() takes seconds.
        // So we need to calculate: (expiresAt - now)
        
        $expiresAt = $raw['time'];
        
        if ($expiresAt === 9999999999) {
            $seconds = 0; // Forever logic in put() is usually different or we pass a huge number?
            // Actually put() handles creating expiration.
            // If we want to preserve expiration, we should probably manually write it or calculate diff.
            // However, `put` expects duration.
            
            // If it's effectively forever:
             $this->forever($key, $new);
        } else {
             $seconds = $expiresAt - time();
             if ($seconds <= 0) {
                 // It expired just now?
                 $this->forget($key);
                 // Start fresh
                 $this->forever($key, $new);
             } else {
                 $this->put($key, $new, $seconds);
             }
        }
        
        return $new;
    }

    /**
     * Decrement the value of an item in the cache.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return int|bool
     */
    public function decrement($key, $value = 1)
    {
        return $this->increment($key, $value * -1);
    }

    /**
     * Store an item in the cache indefinitely.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return bool
     */
    public function forever($key, $value)
    {
        return $this->put($key, $value, 0);
    }

    /**
     * Remove an item from the cache.
     *
     * @param  string  $key
     * @return bool
     */
    public function forget($key)
    {
        if ($this->files->exists($file = $this->path($key))) {
            return $this->files->delete($file);
        }

        return false;
    }

    /**
     * Remove all items from the cache.
     *
     * @return bool
     */
    public function flush()
    {
        if (! $this->files->isDirectory($this->directory)) {
            return false;
        }

        foreach ($this->files->directories($this->directory) as $directory) {
            if (! $this->files->deleteDirectory($directory)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get the cache key prefix.
     *
     * @return string
     */
    public function getPrefix()
    {
        return '';
    }

    /**
     * Retrieve an item and expiry time from the cache by key.
     *
     * @param  string  $key
     * @return array|null
     */
    protected function getPayload($key)
    {
        $path = $this->path($key);

        try {
            $contents = $this->files->get($path, true);
            
            if (empty($contents)) {
                return null;
            }
            
            $expire = substr($contents, 0, 10);
            
            if (time() >= $expire) {
                $this->forget($key);
                return null;
            }

            $data = unserialize(substr($contents, 10));

            $time = $expire;

            return compact('data', 'time');
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Get the full path for the given cache key.
     *
     * @param  string  $key
     * @return string
     */
    protected function path($key)
    {
        $parts = array_slice(str_split($hash = sha1($key), 2), 0, 2);

        return $this->directory.'/'.implode('/', $parts).'/'.$hash;
    }

    /**
     * Get the expiration time based on the given seconds.
     *
     * @param  int  $seconds
     * @return int
     */
    protected function expiration($seconds)
    {
        $time = $seconds === 0 || $seconds === null ? 9999999999 : time() + $seconds;

        return $time;
    }

    /**
     * Ensure the cache directory exists.
     *
     * @param  string  $path
     * @return void
     */
    protected function ensureCacheDirectoryExists($path)
    {
        if (! $this->files->exists(dirname($path))) {
            $this->files->makeDirectory(dirname($path), 0777, true, true);
        }
    }
    
    /**
     * Ensure proper permissions for a file.
     *
     * @param  string  $path
     * @return void
     */
    protected function ensurePermissions($path)
    {
        if (! is_null($this->permission)) {
            chmod($path, $this->permission);
        }
    }
}
