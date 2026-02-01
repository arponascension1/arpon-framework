<?php

namespace Arpon\Contracts\Cookie;

interface CookieJar
{
    public function make($name, $value, $minutes = 0, $path = null, $domain = null, $secure = null, $httpOnly = true, $sameSite = null);
    public function forever($name, $value, $path = null, $domain = null, $secure = null, $httpOnly = true, $sameSite = null);
    public function forget($name, $path = null, $domain = null);
    public function hasQueued($name);
    public function queued($name, $default = null);
    public function queue(\Arpon\Cookie\Cookie $cookie);
    public function unqueue($name);
    public function setDefaultPath($path);
    public function setDefaultDomain($domain);
    public function setDefaultSecure($secure);
    public function setDefaultHttpOnly($httpOnly);
    public function setDefaultSameSite($sameSite);
    public function getPath();
    public function getDomain();
    public function isSecure();
    public function isHttpOnly();
    public function getSameSite();
}
