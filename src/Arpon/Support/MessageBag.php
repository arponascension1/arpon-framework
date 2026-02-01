<?php

namespace Arpon\Support;

class MessageBag
{
    protected $messages = [];

    public function __construct(array $messages = [])
    {
        foreach ($messages as $key => $value) {
            foreach ((array) $value as $message) {
                $this->add($key, $message);
            }
        }
    }

    public function add($key, $message)
    {
        $this->messages[$key][] = $message;
        return $this;
    }

    public function all()
    {
        $results = [];

        foreach ($this->messages as $messages) {
            $results = array_merge($results, $messages);
        }

        return $results;
    }

    public function get($key, $format = null)
    {
        if (isset($this->messages[$key])) {
            return $this->messages[$key];
        }

        return [];
    }

    public function first($key = null, $format = null)
    {
        $messages = $key ? $this->get($key) : $this->all();

        return count($messages) > 0 ? $messages[0] : '';
    }

    public function has($key)
    {
        return isset($this->messages[$key]);
    }

    public function any()
    {
        return count($this->messages) > 0;
    }

    public function count()
    {
        return count($this->all());
    }

    public function toArray()
    {
        return $this->messages;
    }
}
