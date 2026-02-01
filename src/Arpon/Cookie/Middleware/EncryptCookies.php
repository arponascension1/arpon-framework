<?php

namespace Arpon\Cookie\Middleware;

use Closure;
use Arpon\Encryption\Encrypter;
use Arpon\Http\Request;
use Arpon\Http\Response;
use Arpon\Cookie\Cookie;

class EncryptCookies
{
    /**
     * The encrypter instance.
     *
     * @var \Arpon\Encryption\Encrypter
     */
    protected $encrypter;

    /**
     * The names of the cookies that should not be encrypted.
     *
     * @var array
     */
    protected $except = [];

    /**
     * Create a new CookieGuard instance.
     *
     * @param  \Arpon\Encryption\Encrypter  $encrypter
     * @return void
     */
    public function __construct(Encrypter $encrypter)
    {
        $this->encrypter = $encrypter;
    }

    /**
     * Disable encryption for the given cookie name(s).
     *
     * @param  string|array  $name
     * @return void
     */
    public function disableFor($name)
    {
        $this->except = array_merge($this->except, (array) $name);
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Arpon\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        return $this->encrypt($next($this->decrypt($request)));
    }

    /**
     * Decrypt the cookies on the request.
     *
     * @param  \Arpon\Http\Request  $request
     * @return \Arpon\Http\Request
     */
    protected function decrypt(Request $request)
    {
        $cookies = $request->cookies();
        $newCookies = $cookies;
        
        foreach ($cookies as $key => $cookie) {
            if ($this->isDisabled($key)) {
                continue;
            }

            try {
                $value = $this->decryptCookie($key, $cookie);
                $newCookies[$key] = $value;
            } catch (\Exception $e) {
                $newCookies[$key] = null;
            }
        }
        
        $request->setCookies($newCookies);

        return $request;
    }

    /**
     * Decrypt the given cookie and return the value.
     *
     * @param  string  $name
     * @param  string|array  $cookie
     * @return string|array
     */
    protected function decryptCookie($name, $cookie)
    {
        if (is_array($cookie)) {
            foreach ($cookie as $key => $value) {
                $cookie[$key] = $this->decryptCookie($name.'['.$key.']', $value);
            }

            return $cookie;
        }

        return $this->encrypter->decrypt($cookie, static::serialized($name));
    }

    /**
     * Encrypt the cookies on an outgoing response.
     *
     * @param  \Arpon\Http\Response  $response
     * @return \Arpon\Http\Response
     */
    protected function encrypt(Response $response)
    {
        // Get queued cookies from CookieJar via app container
        if (app()->bound('cookie')) {
            $cookieJar = app('cookie');
            $queuedCookies = $cookieJar->getQueuedCookies();
            
            foreach ($queuedCookies as $name => $cookie) {
                if ($this->isDisabled($name)) {
                    continue;
                }
                
                if ($cookie instanceof Cookie) {
                    $encryptedValue = $this->encrypter->encrypt($cookie->getValue(), static::serialized($name));
                    
                    // Create new cookie with encrypted value using existing properties
                    $encryptedCookie = new Cookie(
                        $name,
                        $encryptedValue,
                        $cookie->getMinutes(),
                        $cookie->getPath(),
                        $cookie->getDomain(),
                        $cookie->isSecure(),
                        $cookie->isHttpOnly(),
                        $cookie->getSameSite()
                    );
                    
                    // Re-queue the encrypted cookie
                    $cookieJar->queue($encryptedCookie);
                }
            }
        }

        return $response;
    }

    /**
     * Determine whether encryption has been disabled for the given cookie.
     *
     * @param  string  $name
     * @return bool
     */
    public function isDisabled($name)
    {
        return in_array($name, $this->except);
    }

    /**
     * Determine if the cookie contents should be serialized.
     *
     * @param  string  $name
     * @return bool
     */
    public static function serialized($name)
    {
        return true;
    }
}