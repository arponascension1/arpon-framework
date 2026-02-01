<?php

namespace Arpon\Config;

use ArrayAccess;

class Repository implements ArrayAccess
{
    protected $items = [];

    public function __construct(array $items = [])
    {
        $this->items = $items;
    }

    public function has($key)
    {
        return !is_null($this->get($key));
    }

    public function get($key, $default = null)
    {
        if (is_null($key)) {
            return $this->items;
        }

        if (isset($this->items[$key])) {
            return $this->items[$key];
        }

        $items = $this->items;

        foreach (explode('.', $key) as $segment) {
            if (!is_array($items) || !array_key_exists($segment, $items)) {
                return $default;
            }

            $items = $items[$segment];
        }

        return $items;
    }

    public function set($key, $value = null)
    {
        $keys = is_array($key) ? $key : [$key => $value];

        foreach ($keys as $arrayKey => $arrayValue) {
            $this->setArray($arrayKey, $arrayValue);
        }

        return $this;
    }

    protected function setArray($key, $value)
    {
        $keys = explode('.', $key);

        $items = &$this->items;

        while (count($keys) > 1) {
            $key = array_shift($keys);

            if (!isset($items[$key]) || !is_array($items[$key])) {
                $items[$key] = [];
            }

            $items = &$items[$key];
        }

        $items[array_shift($keys)] = $value;

        return $value;
    }

    public function prepend($key, $value)
    {
        $array = $this->get($key, []);

        array_unshift($array, $value);

        $this->set($key, $array);

        return $this;
    }

    public function push($key, $value)
    {
        $array = $this->get($key, []);

        $array[] = $value;

        $this->set($key, $array);

        return $this;
    }

    public function all()
    {
        return $this->items;
    }

    public function forget($key)
    {
        $keys = is_array($key) ? $key : func_get_args();

        foreach ($keys as $arrayKey) {
            $this->forgetArray($arrayKey);
        }

        return $this;
    }

    protected function forgetArray($key)
    {
        $keys = explode('.', $key);

        $items = &$this->items;

        while (count($keys) > 1) {
            $key = array_shift($keys);

            if (!isset($items[$key]) || !is_array($items[$key])) {
                return;
            }

            $items = &$items[$key];
        }

        unset($items[array_shift($keys)]);
    }

    public function flush()
    {
        $this->items = [];

        return $this;
    }

    public function offsetExists($key): bool
    {
        return $this->has($key);
    }

    public function offsetGet($key): mixed
    {
        return $this->get($key);
    }

    public function offsetSet($key, $value): void
    {
        $this->set($key, $value);
    }

    public function offsetUnset($key): void
    {
        $this->forget($key);
    }
}
