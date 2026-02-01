<?php

namespace Arpon\Http;

use Arpon\Support\Arr;
use Arpon\Support\Facades\Session;

class Request
{
    protected $query;
    protected $request;
    protected $files;
    protected $cookies;
    protected $headers;
    protected $server;
    protected $content;
    protected $json;
    protected $routeResolver;
    protected $session;

    public function __construct(
        array $query = [],
        array $request = [],
        array $files = [],
        array $cookies = [],
        array $headers = [],
        array $server = [],
        $content = null
    ) {
        $this->query = $query;
        $this->request = $request;
        $this->files = $this->convertUploadedFiles($files);
        $this->cookies = $cookies;
        $this->headers = array_change_key_case($headers, CASE_LOWER);
        $this->server = $server;
        $this->content = $content;
    }

    /**
     * Convert $_FILES array to UploadedFile instances.
     */
    protected function convertUploadedFiles(array $files): array
    {
        $converted = [];
        
        foreach ($files as $key => $file) {
            if (is_array($file) && isset($file['tmp_name'])) {
                // Check if it's a multi-file upload
                if (is_array($file['tmp_name'])) {
                    $converted[$key] = $this->convertFilesArray($file);
                } else {
                    // Skip if no file was uploaded or if there's an error
                    if (empty($file['tmp_name']) || (isset($file['error']) && $file['error'] !== UPLOAD_ERR_OK)) {
                        continue;
                    }
                    
                    // Single file
                    $converted[$key] = new UploadedFile(
                        $file['tmp_name'],
                        $file['name'],
                        $file['type'] ?? '',
                        $file['size'] ?? 0,
                        $file['error'] ?? UPLOAD_ERR_OK
                    );
                }
            }
        }
        
        return $converted;
    }

    /**
     * Convert multi-file array to UploadedFile instances.
     */
    protected function convertFilesArray(array $file): array
    {
        $files = [];
        foreach ($file['tmp_name'] as $key => $tmpName) {
            $files[$key] = new UploadedFile(
                $tmpName,
                $file['name'][$key],
                $file['type'][$key] ?? '',
                $file['size'][$key] ?? 0,
                $file['error'][$key] ?? UPLOAD_ERR_OK
            );
        }
        return $files;
    }

    public static function capture()
    {
        return new static(
            $_GET,
            $_POST,
            $_FILES,
            $_COOKIE,
            function_exists('getallheaders') ? getallheaders() : [],
            $_SERVER,
            file_get_contents('php://input')
        );
    }

    /**
     * Create a new Request instance from a URI.
     *
     * @param  string  $uri
     * @param  string  $method
     * @param  array  $parameters
     * @param  array  $cookies
     * @param  array  $files
     * @param  array  $server
     * @param  string|null  $content
     * @return static
     */
    public static function create($uri, $method = 'GET', $parameters = [], $cookies = [], $files = [], $server = [], $content = null)
    {
        $server = array_merge($server, [
            'SERVER_NAME' => 'localhost',
            'SERVER_PORT' => 80,
            'HTTP_HOST' => 'localhost',
            'HTTP_USER_AGENT' => 'Arpon/1.0',
            'HTTP_ACCEPT' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'HTTP_ACCEPT_LANGUAGE' => 'en-us,en;q=0.5',
            'HTTP_ACCEPT_CHARSET' => 'ISO-8859-1,utf-8;q=0.7,*;q=0.7',
            'REMOTE_ADDR' => '127.0.0.1',
            'SCRIPT_NAME' => '',
            'SCRIPT_FILENAME' => '',
            'SERVER_PROTOCOL' => 'HTTP/1.1',
            'REQUEST_TIME' => time(),
        ]);

        $server['REQUEST_METHOD'] = strtoupper($method);

        $components = parse_url($uri);
        if (isset($components['host'])) {
            $server['SERVER_NAME'] = $components['host'];
            $server['HTTP_HOST'] = $components['host'];
        }

        if (isset($components['scheme']) && $components['scheme'] === 'https') {
            $server['HTTPS'] = 'on';
            $server['SERVER_PORT'] = 443;
        }

        if (isset($components['port'])) {
            $server['SERVER_PORT'] = $components['port'];
            $server['HTTP_HOST'] .= ':' . $components['port'];
        }

        if (isset($components['user'])) {
            $server['PHP_AUTH_USER'] = $components['user'];
        }

        if (isset($components['pass'])) {
            $server['PHP_AUTH_PW'] = $components['pass'];
        }

        if (!isset($components['path'])) {
            $components['path'] = '/';
        }

        $queryString = $components['query'] ?? '';
        $server['REQUEST_URI'] = $components['path'] . ($queryString !== '' ? '?' . $queryString : '');
        $server['QUERY_STRING'] = $queryString;

        parse_str($queryString, $query);

        $requestParams = [];
        if (in_array($server['REQUEST_METHOD'], ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            $requestParams = $parameters;
        } else {
            $query = array_merge($query, $parameters);
        }

        // Extract headers from server
        $headers = [];
        foreach ($server as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $headers[substr($key, 5)] = $value;
            } elseif (in_array($key, ['CONTENT_TYPE', 'CONTENT_LENGTH', 'CONTENT_MD5'])) {
                $headers[$key] = $value;
            }
        }

        return new static($query, $requestParams, $files, $cookies, $headers, $server, $content);
    }

    /**
     * Create a request from the PHP globals.
     *
     * @return static
     */
    public static function createFromGlobals()
    {
        $request = static::create(
            $_SERVER['REQUEST_URI'] ?? '/',
            $_SERVER['REQUEST_METHOD'] ?? 'GET',
            $_GET,
            $_COOKIE,
            $_FILES,
            $_SERVER
        );

        return $request;
    }

    public function getPathInfo()
    {
        $requestUri = $this->getRequestUri();

        if (false !== $pos = strpos($requestUri, '?')) {
            $requestUri = substr($requestUri, 0, $pos);
        }

        return '/' . ltrim($requestUri, '/');
    }

    public function getRequestUri()
    {
        $requestUri = '';

        if ($this->server['REQUEST_URI'] ?? '') {
            $requestUri = $this->server['REQUEST_URI'];
        }

        return $requestUri;
    }

    public function getMethod()
    {
        $method = $this->server['REQUEST_METHOD'] ?? 'GET';
        
        // Handle method spoofing for PUT, PATCH, DELETE
        if ($method === 'POST' && isset($this->request['_method'])) {
            $spoofedMethod = strtoupper($this->request['_method']);
            if (in_array($spoofedMethod, ['PUT', 'PATCH', 'DELETE'])) {
                return $spoofedMethod;
            }
        }
        
        return $method;
    }

    public function isMethod($method)
    {
        return strtoupper($this->getMethod()) === strtoupper($method);
    }

    /**
     * Get the request method.
     *
     * @return string
     */
    public function method()
    {
        return $this->getMethod();
    }

    /**
     * Get the current path info for the request.
     *
     * @return string
     */
    public function path()
    {
        return $this->getPathInfo();
    }

    /**
     * Get the query string parameters.
     *
     * @param  string|null  $key
     * @param  mixed  $default
     * @return array|mixed
     */
    public function query($key = null, $default = null)
    {
        if (is_null($key)) {
            return $this->query;
        }

        return Arr::get($this->query, $key, $default);
    }

    public function get($key, $default = null)
    {
        return Arr::get($this->query, $key, $default);
    }

    public function post($key, $default = null)
    {
        return Arr::get($this->request, $key, $default);
    }

    public function input($key, $default = null)
    {
        $value = $this->get($key);

        if ($value === null) {
            $value = $this->post($key);
        }

        // Check route parameters if not found in query or post
        if ($value === null && $this->routeResolver) {
            $route = call_user_func($this->routeResolver);
            if ($route) {
                $value = $route->parameter($key);
            }
        }

        return $value ?? $default;
    }

    /**
     * Get an input parameter (alias for input method).
     *
     * @param  string  $key
     * @param  mixed  $default
     * @return mixed
     */
    public function param($key, $default = null)
    {
        return $this->input($key, $default);
    }

    public function all()
    {
        $data = array_merge($this->query, $this->request, $this->files);
        
        // Include route parameters if available
        if ($this->routeResolver && $route = call_user_func($this->routeResolver)) {
            $data = array_merge($data, $route->parameters());
        }
        
        return $data;
    }

    public function only($keys)
    {
        $keys = is_array($keys) ? $keys : func_get_args();

        return Arr::only($this->all(), $keys);
    }

    public function except($keys)
    {
        $keys = is_array($keys) ? $keys : func_get_args();

        return Arr::except($this->all(), $keys);
    }

    public function has($key)
    {
        return Arr::has($this->query, $key) || Arr::has($this->request, $key);
    }

    public function filled($key)
    {
        $keys = is_array($key) ? $key : func_get_args();

        foreach ($keys as $value) {
            if ($this->isEmptyString($value)) {
                return false;
            }
        }

        return true;
    }

    protected function isEmptyString($key)
    {
        $value = $this->input($key);

        return ! is_bool($value) && ! is_array($value) && trim((string) $value) === '';
    }

    public function header($key, $default = null)
    {
        $lowercaseKey = strtolower($key);

        // Check for standard header format (content-type)
        if (isset($this->headers[$lowercaseKey])) {
            return $this->headers[$lowercaseKey];
        }

        // Check for normalized header format (content_type)
        // because headers extracted from the server use underscores 
        $normalized = str_replace('-', '_', $lowercaseKey);
        if (isset($this->headers[$normalized])) {
            return $this->headers[$normalized];
        }

        return $default;
    }

    /**
     * Get all headers.
     *
     * @return array
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * Get the bearer token from the request headers.
     *
     * @return string|null
     */
    public function bearerToken()
    {
        $header = $this->header('Authorization', '');

        if (str_starts_with($header, 'Bearer ')) {
            return substr($header, 7);
        }

        return null;
    }

    /**
     * Get the password for the request.
     *
     * @return string|null
     */
    public function getPassword()
    {
        return $this->server('PHP_AUTH_PW');
    }

    public function hasHeader($key)
    {
        // Normalize header name to lower-case and replace dashes with underscores
        // to match how headers are stored (e.g. CONTENT_TYPE -> content_type).
        $normalized = str_replace('-', '_', strtolower($key));
        return Arr::has($this->headers, $normalized);
    }

    public function cookies()
    {
        return $this->cookies ?? [];
    }

    public function setCookies(array $cookies)
    {
        $this->cookies = $cookies;
        return $this;
    }

    public function cookie($key, $default = null)
    {
        $cookies = $this->cookies();
        return $cookies[$key] ?? $default;
    }

    public function server($key, $default = null)
    {
        return $this->server[$key] ?? $default;
    }

    public function ip()
    {
        // Check for IP in various headers (for proxy/load balancer support)
        if ($this->server('HTTP_CLIENT_IP')) {
            return $this->server('HTTP_CLIENT_IP');
        }
        
        if ($this->server('HTTP_X_FORWARDED_FOR')) {
            $ips = explode(',', $this->server('HTTP_X_FORWARDED_FOR'));
            return trim($ips[0]);
        }
        
        if ($this->server('HTTP_X_FORWARDED')) {
            return $this->server('HTTP_X_FORWARDED');
        }
        
        if ($this->server('HTTP_FORWARDED_FOR')) {
            return $this->server('HTTP_FORWARDED_FOR');
        }
        
        if ($this->server('HTTP_FORWARDED')) {
            return $this->server('HTTP_FORWARDED');
        }
        
        return $this->server('REMOTE_ADDR', '0.0.0.0');
    }

    public function hasCookie($key)
    {
        return Arr::has($this->cookies, $key);
    }

    public function file($key)
    {
        $file = Arr::get($this->files, $key);

        if (!$file) {
            return null;
        }

        // Already converted to UploadedFile in constructor
        return $file;
    }

    public function hasFile($key)
    {
        $file = $this->file($key);

        if (!$file) {
            return false;
        }

        if ($file instanceof UploadedFile) {
            return $file->isValid();
        }

        if (is_array($file)) {
            foreach ($file as $f) {
                if ($f instanceof UploadedFile && $f->isValid()) {
                    return true;
                }
            }
        }

        return false;
    }

    public function isJson()
    {
        return $this->hasHeader('Content-Type') && 
               str_contains($this->header('Content-Type'), 'application/json');
    }

    public function isXmlHttpRequest()
    {
        return $this->header('X-Requested-With') === 'XMLHttpRequest';
    }

    public function isAjax()
    {
        return $this->isXmlHttpRequest();
    }

    public function expectsJson()
    {
        return $this->isJson() || $this->isAjax();
    }

    public function wantsJson()
    {
        return $this->expectsJson();
    }

    public function is(...$patterns)
    {
        $path = trim($this->getPathInfo(), '/');
        if ($path === '') {
            $path = '/';
        }

        foreach ($patterns as $pattern) {
            $pattern = trim($pattern, '/');
            if ($pattern === '') {
                $pattern = '/';
            }
            
            // Replace wildcards with regex equivalents
            $regex = str_replace('\*', '.*', preg_quote($pattern, '#'));
            
            if (preg_match('#^' . $regex . '\z#u', $path)) {
                return true;
            }
        }

        return false;
    }

    public function json($key = null, $default = null)
    {
        if (!$this->json) {
            $this->json = json_decode($this->getContent(), true) ?? [];
        }

        if ($key === null) {
            return $this->json;
        }

        return Arr::get($this->json, $key, $default);
    }

    public function getContent()
    {
        return $this->content;
    }

    public function getBaseUrl()
    {
        $scheme = $this->getScheme();
        $host = $this->getHost();
        $port = $this->getPort();

        $baseUrl = $scheme . '://' . $host;

        if (($scheme === 'http' && $port != 80) || ($scheme === 'https' && $port != 443)) {
            $baseUrl .= ':' . $port;
        }

        return $baseUrl;
    }

    public function root()
    {
        return $this->getBaseUrl();
    }

    public function getUrl()
    {
        return $this->getBaseUrl() . $this->getRequestUri();
    }

    /**
     * Get the URL (no query string) for the request.
     *
     * @return string
     */
    public function url()
    {
        return $this->getBaseUrl() . $this->getPathInfo();
    }

    public function getFullUrl()
    {
        return $this->getUrl();
    }

    public function fullUrl()
    {
        return $this->getFullUrl();
    }

    public function getUri()
    {
        return $this->getRequestUri();
    }

    public function getSchemeAndHttpHost()
    {
        return $this->getBaseUrl();
    }

    public function getHost()
    {
        $host = $this->server['HTTP_HOST'] ?? 'localhost';

        if (strpos($host, ':') !== false) {
            $host = explode(':', $host)[0];
        }

        return $host;
    }

    public function getScheme()
    {
        return $this->server['HTTPS'] ?? false ? 'https' : 'http';
    }

    public function getPort()
    {
        return $this->server['SERVER_PORT'] ?? 80;
    }

    public function getClientIp()
    {
        return $this->server['REMOTE_ADDR'] ?? '127.0.0.1';
    }

    public function getUserAgent()
    {
        return $this->header('User-Agent');
    }

    public function setRouteResolver(callable $resolver)
    {
        $this->routeResolver = $resolver;
    }

    public function route($param = null, $default = null)
    {
        if (!$this->routeResolver) {
            return $default;
        }

        $route = call_user_func($this->routeResolver);

        if ($param === null) {
            return $route;
        }

        return $route ? $route->parameter($param, $default) : $default;
    }


    /**
     * Get the current route name.
     *
     * @return string|null
     */
    public function currentRouteName()
    {
        if (!$this->routeResolver) {
            return null;
        }

        $route = call_user_func($this->routeResolver);
        
        return $route ? $route->getName() : null;
    }

    /**
     * Determine if the current route matches a given pattern.
     *
     * @param  mixed  ...$patterns
     * @return bool
     */
    public function routeIs(...$patterns)
    {
        $currentRouteName = $this->currentRouteName();

        if (is_null($currentRouteName)) {
            return false;
        }

        // Flatten patterns if an array is passed
        $patterns = $this->flattenPatterns($patterns);

        foreach ($patterns as $pattern) {
            if ($this->matchesPattern($currentRouteName, $pattern)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine if the current route matches a given pattern (alias).
     *
     * @param  mixed  ...$patterns
     * @return bool
     */
    public function currentRouteNamed(...$patterns)
    {
        return $this->routeIs(...$patterns);
    }

    /**
     * Flatten pattern arguments.
     *
     * @param  array  $patterns
     * @return array
     */
    protected function flattenPatterns(array $patterns)
    {
        $flattened = [];

        foreach ($patterns as $pattern) {
            if (is_array($pattern)) {
                $flattened = array_merge($flattened, $this->flattenPatterns($pattern));
            } else {
                $flattened[] = $pattern;
            }
        }

        return $flattened;
    }

    /**
     * Check if a string matches a pattern (Laravel-style).
     *
     * @param  string  $value
     * @param  string  $pattern
     * @return bool
     */
    protected function matchesPattern($value, $pattern)
    {
        // Exact match
        if ($pattern === $value) {
            return true;
        }

        // Convert wildcard pattern to regex
        $pattern = preg_quote($pattern, '#');
        
        // Replace escaped \* with .* for wildcard matching
        $pattern = str_replace('\*', '.*', $pattern);

        return (bool) preg_match('#^' . $pattern . '\z#u', $value);
    }

    public function validate(array $rules, array $messages = [], array $attributes = [])
    {
        return validator($this->all(), $rules, $messages, $attributes)->validate();
    }

    public function validateWithBag($errorBag, array $rules, array $messages = [], array $attributes = [])
    {
        return validator($this->all(), $rules, $messages, $attributes)->validate();
    }

    public function session()
    {
        if (!$this->session) {
            $this->session = app('session');
        }
        
        return $this->session;
    }

    public function setSession($session)
    {
        $this->session = $session;
        
        return $this;
    }

    public function user($guard = null)
    {
        return app('auth')->guard($guard)->user();
    }

    /**
     * Merge new input into the request's input array.
     *
     * @param  array  $input
     * @return $this
     */
    public function merge(array $input)
    {
        $this->request = array_merge($this->request, $input);
        
        return $this;
    }

    /**
     * Replace the request's input with the given array.
     *
     * @param  array  $input
     * @return $this
     */
    public function replace(array $input)
    {
        $this->request = $input;
        
        return $this;
    }

    public function __get($key)
    {
        return $this->input($key);
    }
}
