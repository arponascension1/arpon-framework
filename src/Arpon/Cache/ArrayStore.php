<?php

namespace Arpon\Cache;

use Arpon\Contracts\Cache\Store;
use Arpon\Support\Carbon;

class ArrayStore implements Store
{
    /**
     * The array of stored values.
     *
     * @var array
     */
    protected $storage = [];

    /**
     * Retrieve an item from the cache by key.
     *
     * @param  string|array  $key
     * @return mixed
     */
    public function get($key)
    {
        if (! isset($this->storage[$key])) {
            return null;
        }

        $item = $this->storage[$key];

        $expiresAt = $item['expiresAt'] ?? 0;

        if ($expiresAt !== 0 && (time() > $expiresAt)) {
            $this->forget($key);

            return null;
        }

        return $item['value'];
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
        $this->storage[$key] = [
            'value' => $value,
            'expiresAt' => $this->calculateExpiration($seconds),
        ];

        return true;
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
            $this->put($key, $value, $seconds);
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
        $current = $this->get($key);

        if ($current === null) {
            // Laravel behavior: if item doesn't exist, it's not created.
            // Wait, actually Laravel Redis/Memcached stores might create it, but ArrayStore?
            // Let's check typical behavior. Usually increment works on existing items or treats null as 0.
            // But if it's expired, it's gone.
            // For ArrayStore, let's treat it as initializing to 0 then adding value if it doesn't exist, 
            // but we need a TTL. If we just increment, what's the TTL? 
            // Laravel's `increment` operation usually returns the new value.
            // If the key doesn't exist, it usually starts at 0 + value.
            // And it stays forever? Or inherits?
            // In Laravel `ArrayStore`, `increment` updates the value.
            $current = 0;
            // But if we create it, we need to decide on expiration.
            // If it doesn't exist, `ArrayStore` usually creates it with no expiration (forever).
            // Let's assume forever if not present.
            $this->forever($key, $value);
            return $value;
        }
        
        // If it exists, we update the value but keep the expiration.
        // We need to access the raw storage to preserve expiration.
        $item = $this->storage[$key]; // We know it exists and is valid from $this->get($key) check, BUT get() might return null if expired.
        // If get() returned non-null, it's valid.
        
        $newValue = ((int) $current) + ((int) $value);
        
        $this->storage[$key]['value'] = $newValue;
        
        return $newValue;
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
        $this->storage[$key] = [
            'value' => $value,
            'expiresAt' => 0,
        ];

        return true;
    }

    /**
     * Remove an item from the cache.
     *
     * @param  string  $key
     * @return bool
     */
    public function forget($key)
    {
        if (array_key_exists($key, $this->storage)) {
            unset($this->storage[$key]);
            return true;
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
        $this->storage = [];

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
     * Get the expiration time based on the given seconds.
     *
     * @param  int  $seconds
     * @return int
     */
    protected function calculateExpiration($seconds)
    {
        return $this->toTimestamp($seconds);
    }

    /**
     * Get the UNIX timestamp for the given number of seconds.
     *
     * @param  int  $seconds
     * @return int
     */
    protected function toTimestamp($seconds)
    {
        return $seconds > 0 ? time() + $seconds : 0;
    }
}
