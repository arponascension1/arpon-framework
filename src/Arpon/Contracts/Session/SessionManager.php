<?php

namespace Arpon\Contracts\Session;

interface SessionManager
{
    public function start();
    public function getId();
    public function setId($id);
    public function getName();
    public function setName($name);
    public function invalidate($lifetime = null);
    public function regenerate($destroy = false, $lifetime = null);
    public function save();
    public function forget($key);
    public function flush();
    public function get($key, $default = null);
    public function put($key, $value = null);
    public function has($key);
    public function exists($key);
    public function pull($key, $default = null);
    public function push($key, $value);
    public function increment($key, $amount = 1);
    public function decrement($key, $amount = 1);
    public function flash($key, $value);
    public function now($key, $value);
    public function keep($keys = null);
    public function reflash();
    public function flashInput(array $input);
    public function getOldInput($key = null, $default = null);
    public function token();
    public function regenerateToken();
    public function isStarted();
    public function getHandler();
    public function getStore();
    public function setStore(\Arpon\Contracts\Session\SessionStore $store);
}
