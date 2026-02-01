<?php

namespace Arpon\Contracts\Cookie;

interface Cookie
{
    public function getName();
    public function getValue();
    public function getMinutes();
    public function getPath();
    public function getDomain();
    public function isSecure();
    public function isHttpOnly();
    public function getSameSite();
    public function getExpiresTime();
    public function withValue($value);
    public function withPath($path);
    public function withDomain($domain);
    public function withSecure($secure = true);
    public function withHttpOnly($httpOnly = true);
    public function withSameSite($sameSite);
    public function toArray();
}
