<?php

namespace Arpon\Routing;

use Arpon\Http\Request;
use Arpon\Support\Arr;

class UrlGenerator
{
    protected $request;
    protected $routes = [];
    protected $root;
    protected $forceScheme;
    protected $forceRootUrl;
    protected $cachedScheme;
    protected $cachedRoot;

    public function __construct(Request $request, $root = null)
    {
        $this->request = $request;
        $this->root = $root ?: $request->root();
    }

    public function to($path, $extra = [], $secure = null)
    {
        // Normalize parameters: allow calling to($path, true) as shorthand for secure=true
        if (is_bool($extra) && is_null($secure)) {
            $secure = $extra;
            $extra = [];
        }

        if ($this->isValidUrl($path)) {
            return $path;
        }

        

        // Ensure $extra is an array (defensive). If a scalar/boolean got here,
        // treat as no parameters to avoid http_build_query producing "0=1".
        $extra = is_array($extra) ? $extra : [];

        // If the path already contains values for some parameters (for example
        // route placeholders were already replaced and the value appears in the
        // path), drop those keys from $extra so they are not appended again as
        // query parameters. This prevents cases like '/users/1' + ['user'=>1]
        // becoming '/users/1?user=1'.
        if (!empty($extra) && is_array($extra)) {
            foreach ($extra as $k => $v) {
                if (is_scalar($v) && $v !== '' && strpos((string) $path, (string) $v) !== false) {
                    unset($extra[$k]);
                }
            }
        }

    $path = $this->formatPath($path, $extra);
        $root = $this->getRootUrl($secure);

        return $root . '/' . ltrim($path, '/');
    }

    public function secure($path, $parameters = [])
    {
        return $this->to($path, $parameters, true);
    }

    public function asset($path, $secure = null)
    {
        if ($this->isValidUrl($path)) {
            return $path;
        }

        $root = $this->getRootUrl($secure);
        return $root . '/' . ltrim($path, '/');
    }

    public function route($name, $parameters = [], $absolute = true)
    {
        // Allow calling route('name', true) as shorthand for absolute = true
        if (is_bool($parameters) && is_null($absolute)) {
            $absolute = $parameters;
            $parameters = [];
        }

        // Also allow calling route('name', true) when $absolute has default value
        if (is_bool($parameters) && $absolute === true) {
            $absolute = $parameters;
            $parameters = [];
        }

        if (!isset($this->routes[$name])) {
            throw new \InvalidArgumentException("Route [{$name}] not defined.");
        }

        $parameters = Arr::wrap($parameters);

        $uri = $this->routes[$name];

        // If parameters are provided positionally (numeric keys), map them to
        // the named placeholders in the route so they are not treated as
        // leftover query parameters (which previously produced ?0=1).
    preg_match_all('/\{(\w+)\??\}/', (string) $this->routes[$name], $placeholderMatches);
        $placeholders = !empty($placeholderMatches[1]) ? $placeholderMatches[1] : [];

        // If parameters are a list (numeric keys) and there are placeholders,
        // map by position to the placeholder names.
        $isList = array_keys($parameters) === range(0, max(0, count($parameters) - 1));
        if ($isList && !empty($placeholders)) {
            $values = array_values($parameters);
            $mapped = [];
            foreach ($placeholders as $i => $placeholderName) {
                if (array_key_exists($i, $values)) {
                    $mapped[$placeholderName] = $values[$i];
                }
            }

            // Preserve any originally associative parameters (string keys)
            foreach ($parameters as $k => $v) {
                if (!is_int($k)) {
                    $mapped[$k] = $v;
                }
            }

            $parameters = $mapped;
        }

        // Replace named placeholders in the URI with provided parameters
        $uri = $this->replaceRouteParameters($uri, $parameters);

        // If absolute URL is requested and there are leftover parameters, pass
        // them to to() so they are appended as query string (used for signed routes).
        if ($absolute) {
            // Determine any parameters that were not used for URI placeholders
            preg_match_all('/\{(\w+)\??\}/', (string) $this->routes[$name], $matches);
            $used = !empty($matches[1]) ? $matches[1] : [];

            $queryParameters = [];
            foreach ($parameters as $key => $value) {
                if (!in_array($key, $used, true)) {
                    $queryParameters[$key] = $value;
                }
            }

            

            return $this->to($uri, $queryParameters);
        }

        // For relative routes include leftover parameters as query string as well
    preg_match_all('/\{(\w+)\??\}/', (string) $this->routes[$name], $matches);
        $used = !empty($matches[1]) ? $matches[1] : [];

        $queryParameters = [];
        foreach ($parameters as $key => $value) {
            if (!in_array($key, $used, true)) {
                $queryParameters[$key] = $value;
            }
        }

        $path = '/' . ltrim($uri, '/');
        if (!empty($queryParameters) && is_array($queryParameters)) {
            $path .= '?' . http_build_query($queryParameters);
        }

        return $path;
    }

    public function signedRoute($name, $parameters = [], $expiration = null, $absolute = true)
    {
        $parameters = $this->formatParameters($parameters);

        // Prepare parameters for signing (expires included)
        if ($expiration) {
            $parameters['expires'] = $this->availableAt($expiration);
        }

        // Determine the URI for the route (placeholders replaced) so we sign the final path
        if (!isset($this->routes[$name])) {
            throw new \InvalidArgumentException("Route [{$name}] not defined.");
        }

        $uri = $this->routes[$name];
        // Use original parameters for placeholder replacement and cleanup
        $uri = $this->replaceRouteParameters($uri, $parameters);

        // Determine which parameters end up in the query string (not used for placeholders)
        preg_match_all('/\{(\w+)\??\}/', $this->routes[$name], $matches);
        $used = !empty($matches[1]) ? $matches[1] : [];

        $queryParameters = [];
        foreach ($parameters as $key => $value) {
            if (!in_array($key, $used, true)) {
                $queryParameters[$key] = $value;
            }
        }

        // Create signature based on the final URI and the query parameters (including expires)
        $parameters['signature'] = $this->createSignature($uri, $queryParameters);

        return $this->route($name, $parameters, $absolute);
    }

    public function action($action, $parameters = [], $absolute = true)
    {
        // Convert action like "UserController@index" to route name
        $routeName = $this->getRouteNameFromAction($action);
        return $this->route($routeName, $parameters, $absolute);
    }

    public function previous($fallback = false)
    {
        $referer = $this->request->header('referer');
        $url = $referer ?: $fallback;

        if (! $url) {
            return $this->getPreviousUrlFromSession();
        }

        return $url;
    }

    public function current()
    {
        return $this->request->fullUrl();
    }

    public function full()
    {
        return $this->request->fullUrl();
    }

    public function formatPath($path, $parameters = [])
    {
        // Defensive: only build a query string for array parameters.
        if (!empty($parameters) && is_array($parameters)) {
            $path .= '?' . http_build_query($parameters);
        }

        return $path;
    }

    public function isValidUrl($path)
    {
        if (! preg_match('~^(#|//|https?://|mailto:|tel:)~', $path)) {
            return filter_var($path, FILTER_VALIDATE_URL) !== false;
        }

        return true;
    }

    public function getRootUrl($scheme = null)
    {
        if (is_bool($scheme)) {
            $scheme = $scheme ? 'https' : 'http';
        }

        if (is_null($scheme)) {
            if (is_null($this->cachedScheme)) {
                $this->cachedScheme = $this->forceScheme ?: $this->request->getScheme();
            }

            $scheme = $this->cachedScheme;
        }

        $root = $this->forceRootUrl ?: $this->request->root();

        return $this->replaceRootScheme($scheme, $root);
    }

    public function forceScheme($scheme)
    {
        $this->forceScheme = $scheme;
        $this->cachedScheme = null;

        return $this;
    }

    public function forceRootUrl($root)
    {
        $this->forceRootUrl = rtrim($root, '/');
        $this->cachedRoot = null;
        $this->cachedScheme = null; // Clear scheme too as root might imply one

        return $this;
    }

    public function setRoutes(array $routes)
    {
        $this->routes = $routes;
        return $this;
    }

    protected function replaceRouteParameters($uri, $parameters)
    {
        // Convert models to their route key value (usually ID)
        $parameters = $this->formatRouteParameters($parameters);

        foreach ($parameters as $key => $value) {
            if (is_array($value)) {
                continue;
            }

            $value = rawurlencode((string) $value);

            $uri = str_replace('{'.$key.'}', $value, $uri);
            $uri = str_replace('{'.$key.'?}', $value, $uri);
        }

        return preg_replace('/\{(\w+)\?\}/', '', $uri);
    }
    
    protected function formatRouteParameters($parameters)
    {
        $formatted = [];
        
        foreach ((array)$parameters as $key => $value) {
            // Check if it's a model with getRouteKey method
            if (is_object($value) && method_exists($value, 'getRouteKey')) {
                $formatted[$key] = $value->getRouteKey();
            }
            // Check if it's a model with getKey method
            elseif (is_object($value) && method_exists($value, 'getKey')) {
                $formatted[$key] = $value->getKey();
            }
            // Check if it's an object with id property
            elseif (is_object($value) && isset($value->id)) {
                $formatted[$key] = $value->id;
            }
            else {
                $formatted[$key] = $value;
            }
        }
        
        return $formatted;
    }

    protected function replaceParameters($uri, $parameters)
    {
        foreach ($parameters as $key => $value) {
            $uri = str_replace('{' . $key . '}', $value, $uri);
        }
        
        return $uri;
    }

    protected function formatParameters($parameters)
    {
        return is_array($parameters) ? $parameters : [];
    }

    protected function createSignature($route, $parameters)
    {
        $key = config('app.key');
        $parameters = $this->formatParameters($parameters);
        ksort($parameters);

        // Normalize route/path: ensure it does not start with a leading slash for consistent signing
        $routePart = ltrim($route, '/');

        // Ensure consistent parameter types (strings) so signing is stable across
        // URL generation and subsequent parsing via parse_str (which yields strings).
        foreach ($parameters as $k => $v) {
            $parameters[$k] = (string) $v;
        }

    $signature = hash_hmac('sha256', $routePart . '::' . serialize($parameters), $key);

        return $signature;
    }

    /**
     * Determine if a signed URL is valid (not expired and signature matches).
     *
     * @param string $url
     * @return bool
     */
    public function hasValidSignature($url)
    {
        $parts = parse_url($url);

        $path = $parts['path'] ?? '';
        $query = [];
        if (isset($parts['query'])) {
            parse_str($parts['query'], $query);
        }

        // Must have signature
        if (!isset($query['signature'])) {
            return false;
        }

        $signature = $query['signature'];
        unset($query['signature']);

        // Check expiry if present
        if (isset($query['expires']) && time() > (int) $query['expires']) {
            return false;
        }

        $expected = $this->createSignature($path, $query);

        return hash_equals($expected, $signature);
    }

    protected function availableAt($expiration)
    {
        return time() + $expiration;
    }

    protected function getRouteNameFromAction($action)
    {
        // Convert namespace separators to dots, remove Controller@index suffix
        $name = str_replace('\\', '.', $action);
        // Remove common 'Controller@index' suffix or '@index'
        $name = preg_replace('/Controller(@|@?index)?$/i', '', $name);
        // Also remove trailing @index if present
        $name = preg_replace('/@index$/i', '', $name);
        // Replace remaining @ with .
        $name = str_replace('@', '.', $name);

        $name = trim($name, '.');

        return strtolower($name);
    }

    protected function getPreviousUrlFromSession()
    {
        $session = app('session');
        return $session->get('_previous.url');
    }

    protected function replaceRootScheme($scheme, $root)
    {
        if (str_starts_with($root, '//')) {
            return $scheme . ':' . $root;
        }

        $start = strpos($root, '://');
        
        if ($start === false) {
            return $scheme . '://' . $root;
        }
        
        return $scheme . substr($root, $start);
    }
}
