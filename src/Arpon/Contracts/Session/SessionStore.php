<?php

namespace Arpon\Contracts\Session;

interface SessionStore
{
    public function start();
    public function getId();
    public function setId($id);
    public function getName();
    public function setName($name);
    public function get($key, $default = null);
    public function set($key, $value);
    public function put($key, $value); // Alias for set()
    public function has($key);
    public function exists($key);
    public function remove($key);
    public function all();
    public function clear();
    public function save();
    public function destroy();
    public function migrate($destroy = false);
    public function getHandler();
    public function isStarted();
}
