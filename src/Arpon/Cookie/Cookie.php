<?php

namespace Arpon\Cookie;

use Arpon\Contracts\Cookie\Cookie as CookieContract;

class Cookie implements CookieContract
{
    protected $name;
    protected $value;
    protected $minutes;
    protected $path;
    protected $domain;
    protected $secure;
    protected $httpOnly;
    protected $sameSite;
    protected $expiresTime;

    public function __construct($name, $value, $minutes = 0, $path = '/', $domain = null, $secure = false, $httpOnly = true, $sameSite = 'lax')
    {
        $this->name = $name;
        $this->value = $value;
        $this->minutes = $minutes;
        $this->path = $path;
        $this->domain = $domain;
        $this->secure = $secure;
        $this->httpOnly = $httpOnly;
        $this->sameSite = $sameSite;
        
        // Calculate expires time once at creation
        if ($minutes === 0) {
            $this->expiresTime = 0;
        } else {
            $this->expiresTime = time() + ($minutes * 60);
        }
    }

    public function getName()
    {
        return $this->name;
    }

    public function getValue()
    {
        return $this->value;
    }

    public function getMinutes()
    {
        return $this->minutes;
    }

    public function getPath()
    {
        return $this->path;
    }

    public function getDomain()
    {
        return $this->domain;
    }

    public function isSecure()
    {
        return $this->secure;
    }

    public function isHttpOnly()
    {
        return $this->httpOnly;
    }

    public function getSameSite()
    {
        return $this->sameSite;
    }

    public function getExpiresTime()
    {
        return $this->expiresTime;
    }

    public function withValue($value)
    {
        return new static($this->name, $value, $this->minutes, $this->path, $this->domain, $this->secure, $this->httpOnly, $this->sameSite);
    }

    public function withPath($path)
    {
        return new static($this->name, $this->value, $this->minutes, $path, $this->domain, $this->secure, $this->httpOnly, $this->sameSite);
    }

    public function withDomain($domain)
    {
        return new static($this->name, $this->value, $this->minutes, $this->path, $domain, $this->secure, $this->httpOnly, $this->sameSite);
    }

    public function withSecure($secure = true)
    {
        return new static($this->name, $this->value, $this->minutes, $this->path, $this->domain, $secure, $this->httpOnly, $this->sameSite);
    }

    public function withHttpOnly($httpOnly = true)
    {
        return new static($this->name, $this->value, $this->minutes, $this->path, $this->domain, $this->secure, $httpOnly, $this->sameSite);
    }

    public function withSameSite($sameSite)
    {
        return new static($this->name, $this->value, $this->minutes, $this->path, $this->domain, $this->secure, $this->httpOnly, $sameSite);
    }

    public function toArray()
    {
        return [
            'name' => $this->name,
            'value' => $this->value,
            'minutes' => $this->minutes,
            'path' => $this->path,
            'domain' => $this->domain,
            'secure' => $this->secure,
            'http_only' => $this->httpOnly,
            'same_site' => $this->sameSite,
        ];
    }
}
