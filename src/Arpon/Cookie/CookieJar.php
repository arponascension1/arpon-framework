<?php

namespace Arpon\Cookie;

use Arpon\Contracts\Cookie\CookieJar as CookieJarContract;

class CookieJar implements CookieJarContract
{
    protected $path = '/';
    protected $domain = null;
    protected $secure = false;
    protected $httpOnly = true;
    protected $sameSite = 'lax';
    
    /**
     * The cookies that have been queued for the next request.
     *
     * @var array
     */
    protected $queued = [];

    public function make($name, $value, $minutes = 0, $path = null, $domain = null, $secure = null, $httpOnly = true, $sameSite = null)
    {
        $path = $path ?? $this->path;
        $domain = $domain ?? $this->domain;
        $secure = $secure ?? $this->secure;
        $sameSite = $sameSite ?? $this->sameSite;

        return new Cookie($name, $value, $minutes, $path, $domain, $secure, $httpOnly, $sameSite);
    }

    public function forever($name, $value, $path = null, $domain = null, $secure = null, $httpOnly = true, $sameSite = null)
    {
        return $this->make($name, $value, 2628000, $path, $domain, $secure, $httpOnly, $sameSite);
    }

    public function forget($name, $path = null, $domain = null)
    {
        return $this->make($name, null, -2628000, $path, $domain);
    }

    public function hasQueued($name)
    {
        return isset($this->queued[$name]);
    }

    public function queued($name, $default = null)
    {
        return $this->queued[$name] ?? $default;
    }

    public function queue(Cookie $cookie)
    {
        $this->queued[$cookie->getName()] = $cookie;
    }

    public function unqueue($name)
    {
        unset($this->queued[$name]);
    }
    
    /**
     * Get all of the queued cookies.
     *
     * @return array
     */
    public function getQueuedCookies()
    {
        return $this->queued;
    }
    
    /**
     * Set the default path and domain for cookies.
     *
     * @param  string  $path
     * @param  string  $domain
     * @param  bool|null  $secure
     * @param  string|null  $sameSite
     * @return $this
     */
    public function setDefaultPathAndDomain($path, $domain = null, $secure = null, $sameSite = null)
    {
        $this->path = $path;
        
        if ($domain !== null) {
            $this->domain = $domain;
        }
        
        if ($secure !== null) {
            $this->secure = $secure;
        }
        
        if ($sameSite !== null) {
            $this->sameSite = $sameSite;
        }
        
        return $this;
    }

    /**
     * Get a queued cookie by name.
     *
     * @param  string  $name
     * @param  mixed  $default
     * @return Cookie|null
     */
    public function getQueued($name, $default = null)
    {
        return $this->queued($name, $default);
    }

    /**
     * Send all queued cookies with the response.
     *
     * @return void
     */
    public function sendQueuedCookies()
    {
        foreach ($this->queued as $cookie) {
            $cookieOptions = [
                'expires' => $cookie->getExpiresTime(),
                'path' => $cookie->getPath(),
                'domain' => $cookie->getDomain(),
                'secure' => $cookie->isSecure(),
                'httponly' => $cookie->isHttpOnly(),
            ];

            if (version_compare(PHP_VERSION, '7.3.0', '>=')) {
                $cookieOptions['samesite'] = $cookie->getSameSite();
            } else {
                $cookieOptions['path'] .= '; samesite=' . $cookie->getSameSite();
            }

            setcookie($cookie->getName(), $cookie->getValue(), $cookieOptions);
        }
        
        $this->queued = [];
    }

    public function setDefaultPath($path)
    {
        $this->path = $path;
        return $this;
    }

    public function setDefaultDomain($domain)
    {
        $this->domain = $domain;
        return $this;
    }

    public function setDefaultSecure($secure)
    {
        $this->secure = $secure;
        return $this;
    }

    public function setDefaultHttpOnly($httpOnly)
    {
        $this->httpOnly = $httpOnly;
        return $this;
    }

    public function setDefaultSameSite($sameSite)
    {
        $this->sameSite = $sameSite;
        return $this;
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
}
