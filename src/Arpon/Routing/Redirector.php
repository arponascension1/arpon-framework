<?php

namespace Arpon\Routing;

use Arpon\Http\RedirectResponse;
use Arpon\Http\Request;
use Arpon\Routing\UrlGenerator;


class Redirector
{
    protected $generator;
    protected $session;

    public function __construct(UrlGenerator $generator)
    {
        $this->generator = $generator;
    }

    public function home($status = 302)
    {
        return $this->to($this->generator->route('home'), $status);
    }

    public function back($status = 302, $headers = [], $fallback = false)
    {
        $referer = request()->header('referer');
        $current = request()->fullUrl();
        
        // Use referer if it exists and is not the same as the current URL
        if ($referer && $referer !== $current) {
            $url = $referer;
        } else {
            $url = ($fallback ?: $this->generator->previous($fallback));
        }
        
        // Final fallback to home if we still don't have a URL
        $url = $url ?: '/';

        return $this->to($url, $status, $headers);
    }

    public function refresh($status = 302)
    {
        return $this->to(request()->fullUrl(), $status);
    }

    public function guest($path, $status = 302, $headers = [], $request = null)
    {
        $request = $request ?: request();
        $intended = $request->fullUrl();

        $session = app('session');
        $session->put('url.intended', $intended);

        return $this->to($path, $status, $headers);
    }

    public function intended($default = '/', $status = 302, $headers = [])
    {
        $session = app('session');
        $intended = $session->pull('url.intended', $default);

        return $this->to($intended, $status, $headers);
    }

    public function to($path, $status = 302, $headers = [])
    {
        // Ensure path is absolute (starts with /)
        if (!empty($path) && $path[0] !== '/' && !preg_match('#^https?://#', $path)) {
            $path = '/' . $path;
        }
        
        return new RedirectResponse($path, $status, $headers);
    }

    public function away($path, $status = 302, $headers = [])
    {
        return $this->to($path, $status, $headers);
    }

    public function secure($path, $status = 302, $headers = [])
    {
        $url = $this->generator->secure($path);
        return $this->to($url, $status, $headers);
    }

    public function route($route, $parameters = [], $status = 302, $headers = [])
    {
        $url = $this->generator->route($route, $parameters);
        return $this->to($url, $status, $headers);
    }

    public function signedRoute($route, $parameters = [], $expiration = null, $status = 302, $headers = [])
    {
        $url = $this->generator->signedRoute($route, $parameters, $expiration);
        return $this->to($url, $status, $headers);
    }

    public function action($action, $parameters = [], $status = 302, $headers = [])
    {
        $url = $this->generator->action($action, $parameters);
        return $this->to($url, $status, $headers);
    }

    public function setIntendedUrl($url)
    {
        $session = app('session');
        $session->put('url.intended', $url);
        return $this;
    }

    public function getIntendedUrl()
    {
        $session = app('session');
        return $session->get('url.intended');
    }

    public function forgetIntendedUrl()
    {
        $session = app('session');
        $session->forget('url.intended');
        return $this;
    }

    public function getUrlGenerator()
    {
        return $this->generator;
    }
}
