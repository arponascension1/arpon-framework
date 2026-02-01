<?php

namespace Arpon\Http;

class Response
{
    protected $content;
    protected $statusCode = 200;
    protected $headers = [];
    protected $version = '1.1';
    protected $charset = 'UTF-8';

    protected static $statusTexts = [
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        204 => 'No Content',
        301 => 'Moved Permanently',
        302 => 'Found',
        400 => 'Bad Request',
        401 => 'Unauthorized',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        422 => 'Unprocessable Entity',
        500 => 'Internal Server Error',
        503 => 'Service Unavailable',
    ];

    public function __construct($content = '', $statusCode = 200, $headers = [])
    {
        $this->statusCode = $statusCode;
        $this->headers = $headers;
        $this->setContent($content);
    }

    public function setContent($content)
    {
        if ($content instanceof \Arpon\View\View) {
            $content = $content->render();
        } elseif (is_array($content) || is_object($content)) {
            $content = json_encode($content);
            $this->header('Content-Type', 'application/json');
        }

        $this->content = $content;
        return $this;
    }

    public function getContent()
    {
        return $this->content;
    }

    public function setStatusCode($statusCode)
    {
        $this->statusCode = $statusCode;
        return $this;
    }

    public function getStatusCode()
    {
        return $this->statusCode;
    }

    public function setHeader($name, $value)
    {
        $this->headers[$name] = $value;
        return $this;
    }

    public function header($name, $value = null)
    {
        if ($value === null) {
            return $this->headers[$name] ?? null;
        }

        return $this->setHeader($name, $value);
    }

    public function getHeaders()
    {
        return $this->headers;
    }

    public function withHeaders(array $headers)
    {
        foreach ($headers as $name => $value) {
            $this->setHeader($name, $value);
        }

        return $this;
    }

    public function json($data = [], $statusCode = 200, $headers = [])
    {
        $headers['Content-Type'] = 'application/json';
        
        return new Response(json_encode($data), $statusCode, $headers);
    }

    public function redirect($url, $statusCode = 302)
    {
        $this->setHeader('Location', $url);
        $this->setStatusCode($statusCode);
        return $this;
    }

    public function view($view, $data = [], $statusCode = 200)
    {
        if (function_exists('view')) {
            $content = view($view, $data);
            return new Response($content, $statusCode);
        }

        throw new \Exception('View helper not available');
    }

    public function cookie($name, $value, $minutes = 0, $path = '/', $domain = null, $secure = false, $httpOnly = true)
    {
        $cookie = "{$name}={$value}";
        
        if ($minutes > 0) {
            $expires = time() + ($minutes * 60);
            $cookie .= "; expires=" . gmdate('D, d-M-Y H:i:s T', $expires);
        }
        
        if ($path) {
            $cookie .= "; path={$path}";
        }
        
        if ($domain) {
            $cookie .= "; domain={$domain}";
        }
        
        if ($secure) {
            $cookie .= "; secure";
        }
        
        if ($httpOnly) {
            $cookie .= "; httponly";
        }

        $this->setHeader('Set-Cookie', $cookie);
        return $this;
    }

    public function getStatusText()
    {
        return self::$statusTexts[$this->statusCode] ?? 'Unknown Status';
    }

    /**
     * Get the response charset.
     *
     * @return string
     */
    public function getCharset()
    {
        return $this->charset;
    }

    /**
     * Get the response protocol version.
     *
     * @return string
     */
    public function getVersion()
    {
        return $this->version;
    }

    public function send()
    {
        $this->sendHeaders();
        $this->sendContent();
        
        return $this;
    }

    public function sendHeaders()
    {
        if (headers_sent()) {
            return $this;
        }
        
        // Send queued cookies before sending headers
        if (function_exists('app') && app()->bound('cookie')) {
            $cookieJar = app('cookie');
            if (method_exists($cookieJar, 'sendQueuedCookies')) {
                $cookieJar->sendQueuedCookies();
            }
        }

        header(sprintf('HTTP/%s %s %s', $this->version, $this->statusCode, $this->getStatusText()));

        foreach ($this->headers as $name => $value) {
            header("{$name}: {$value}");
        }

        return $this;
    }

    public function sendContent()
    {
        echo $this->content;
        return $this;
    }

    public function __toString()
    {
        return $this->content;
    }

    public static function make($content = '', $statusCode = 200, $headers = [])
    {
        return new static($content, $statusCode, $headers);
    }

    public static function redirectTo($path, $status = 302, $headers = [])
    {
        return new RedirectResponse($path, $status, $headers);
    }

    public static function redirectRoute($route, $parameters = [], $status = 302, $headers = [])
    {
        $url = app('url')->route($route, $parameters);
        return new RedirectResponse($url, $status, $headers);
    }

    public static function redirectBack($status = 302, $headers = [])
    {
        $url = request()->header('referer', '/');
        return new RedirectResponse($url, $status, $headers);
    }

    public static function redirectAway($path, $status = 302, $headers = [])
    {
        return new RedirectResponse($path, $status, $headers);
    }

    public static function redirectGuest($path, $status = 302, $headers = [])
    {
        $request = request();
        $intended = $request->fullUrl();
        
        $session = app('session');
        $session->put('url.intended', $intended);
        
        return new RedirectResponse($path, $status, $headers);
    }

    public static function redirectIntended($default = '/', $status = 302, $headers = [])
    {
        $session = app('session');
        $intended = $session->pull('url.intended', $default);
        
        return new RedirectResponse($intended, $status, $headers);
    }
}
