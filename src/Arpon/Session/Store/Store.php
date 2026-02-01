<?php

namespace Arpon\Session\Store;

use Arpon\Contracts\Session\SessionStore as SessionStoreContract;
use SessionHandlerInterface;

class Store implements SessionStoreContract
{
    protected $id;
    protected $name;
    protected $attributes = [];
    protected $handler;
    protected $started = false;

    public function __construct($name, SessionHandlerInterface $handler, $id = null)
    {
        $this->name = $name;
        $this->handler = $handler;
        $this->setId($id);
    }

    public function start()
    {
        $this->loadSession();

        if (! $this->has('_token')) {
            $this->regenerateToken();
        }

        return $this->started = true;
    }

    protected function loadSession()
    {
        $data = $this->handler->read($this->getId());

        if ($data !== '' && $data !== false) {
            $unserialized = @unserialize($data);
            if (is_array($unserialized)) {
                $this->attributes = array_merge($this->attributes, $unserialized);
            }
        }
    }

    public function save()
    {
        $this->handler->write($this->getId(), serialize($this->attributes));

        $this->started = false;
    }

    public function getId()
    {
        return $this->id;
    }

    public function setId($id)
    {
        $this->id = $id ?: bin2hex(random_bytes(16));
    }

    public function getName()
    {
        return $this->name;
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    public function get($key, $default = null)
    {
        return $this->attributes[$key] ?? $default;
    }

    public function set($key, $value)
    {
        $this->attributes[$key] = $value;
    }

    public function put($key, $value)
    {
        $this->set($key, $value);
    }

    public function has($key)
    {
        return isset($this->attributes[$key]);
    }

    public function exists($key)
    {
        return array_key_exists($key, $this->attributes);
    }

    public function remove($key)
    {
        unset($this->attributes[$key]);
    }

    public function all()
    {
        return $this->attributes;
    }

    public function clear()
    {
        $this->attributes = [];
    }

    public function destroy()
    {
        $this->clear();
        $this->handler->destroy($this->getId());
    }

    public function migrate($destroy = false)
    {
        if ($destroy) {
            $this->handler->destroy($this->getId());
        }

        $this->setId(bin2hex(random_bytes(16)));

        return true;
    }

    public function getHandler()
    {
        return $this->handler;
    }

    public function isStarted()
    {
        return $this->started;
    }

    public function regenerateToken()
    {
        $this->put('_token', bin2hex(random_bytes(32)));
    }

    public function token()
    {
        return $this->get('_token');
    }
}
