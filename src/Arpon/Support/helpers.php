<?php

/**
 * Get an environment variable value.
 *
 * @param string $key
 * @param mixed $default
 * @return mixed
 */

use Arpon\Auth\AuthManager;
use Arpon\Auth\SessionGuard;
use Arpon\Cache\CacheManager;
use Arpon\Http\RedirectResponse;
use Arpon\Routing\Redirector;
use Arpon\Routing\UrlGenerator;
use JetBrains\PhpStorm\NoReturn;

if (!function_exists('env')) {
    function env($key, $default = null)
    {
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);

        if ($value === false) {
            return $default;
        }

        if ($value === null) {
            return $default;
        }

        switch (strtolower($value)) {
            case 'true':
            case '(true)':
                return true;
            case 'false':
            case '(false)':
                return false;
            case 'empty':
            case '(empty)':
                return '';
            case 'null':
            case '(null)':
                return null;
        }

        if (is_string($value) && strlen($value) > 1) {
            $first = $value[0];
            $last = $value[strlen($value) - 1];

            if (($first === '"' && $last === '"') || ($first === "'" && $last === "'")) {
                return substr($value, 1, -1);
            }
        }

        return $value;
    }
}

/**
 * Get or set configuration values.
 *
 * @param string|null $key
 * @param mixed $default
 * @return mixed|\Arpon\Config\Repository
 */
if (!function_exists('config')) {
    function config($key = null, $default = null)
    {
        if (is_null($key)) {
            return app('config');
        }

        return app('config')->get($key, $default);
    }
}

/**
 * Create a new response instance.
 *
 * @param string $content
 * @param int $statusCode
 * @param array $headers
 * @return \Arpon\Http\Response
 */
if (!function_exists('response')) {
    function response($content = '', $statusCode = 200, $headers = [])
    {
        return new \Arpon\Http\Response($content, $statusCode, $headers);
    }
}

/**
 * Render a view and return a response.
 *
 * @param string $view
 * @param array $data
 * @param int $statusCode
 * @return \Arpon\View\View
 */


/**
 * Create a new JSON response instance.
 *
 * @param mixed $data
 * @param int $statusCode
 * @param array $headers
 * @param int $encodingOptions
 * @return \Arpon\Http\JsonResponse
 */
if (!function_exists('json_response')) {
    function json_response($data = [], $statusCode = 200, $headers = [], $encodingOptions = 0)
    {
        return new \Arpon\Http\JsonResponse($data, $statusCode, $headers, $encodingOptions);
    }
}

if (!function_exists('view')) {
    function view($view, $data = [], $statusCode = 200)
    {
        $app = \Arpon\Foundation\Application::getInstance();
        
        if (!$app) {
            throw new \Exception('Application instance not available');
        }

        return $app->make('view')->make($view, $data);
    }
}

/**
 * Get the database path.
 *
 * @param string $path
 * @return string
 */
if (!function_exists('database_path')) {
    function database_path($path = '')
    {
        return app()->databasePath($path);
    }
}

/**
 * Get the resource path.
 *
 * @param string $path
 * @return string
 */
if (!function_exists('resource_path')) {
    function resource_path($path = '')
    {
        return app()->resourcePath($path);
    }
}

/**
 * Get the public path.
 *
 * @param string $path
 * @return string
 */
if (!function_exists('public_path')) {
    function public_path($path = '')
    {
        return app()->publicPath($path);
    }
}

/**
 * Get the base path.
 *
 * @param string $path
 * @return string
 */
if (!function_exists('base_path')) {
    function base_path($path = '')
    {
        return app()->basePath($path);
    }
}

/**
 * Get the storage path.
 *
 * @param string $path
 * @return string
 */
if (!function_exists('storage_path')) {
    function storage_path($path = '')
    {
        return app()->storagePath($path);
    }
}

/**
 * Get the config path.
 *
 * @param string $path
 * @return string
 */
if (!function_exists('config_path')) {
    function config_path($path = '')
    {
        return app()->configPath($path);
    }
}

/**
 * Get the application instance or resolve a binding.
 *
 * @param string|null $abstract
 * @param array $parameters
 * @return mixed|\Arpon\Foundation\Application
 */
if (!function_exists('app')) {
    function app($abstract = null, array $parameters = [])
    {
        $instance = \Arpon\Foundation\Application::getInstance();
        
        if (is_null($abstract)) {
            return $instance;
        }

        return $instance->make($abstract, $parameters);
    }
}

/**
 * Generate an asset URL.
 *
 * @param string $path
 * @param bool|null $secure
 * @return string
 */
if (!function_exists('asset')) {
    function asset($path, $secure = null)
    {
        $baseUrl = config('app.asset_url') ?: config('app.url', 'http://localhost');
        return rtrim($baseUrl, '/') . '/' . ltrim($path, '/');
    }
}

/**
 * Get or set session values.
 *
 * @param string|array|null $key
 * @param mixed $default
 * @return mixed|\Arpon\Session\SessionManager
 */
if (!function_exists('session')) {
    function session($key = null, $default = null)
    {
        if (is_null($key)) {
            return app('session');
        }

        if (is_array($key)) {
            return app('session')->put($key);
        }

        return app('session')->get($key, $default);
    }
}

/**
 * Create or retrieve a cookie.
 *
 * @param string|null $name
 * @param string|null $value
 * @param int $minutes
 * @param string|null $path
 * @param string|null $domain
 * @param bool|null $secure
 * @param bool $httpOnly
 * @param string|null $sameSite
 * @return \Arpon\Cookie\Cookie|\Arpon\Cookie\CookieJar
 */
if (!function_exists('cookie')) {
    function cookie($name = null, $value = null, $minutes = 0, $path = null, $domain = null, $secure = null, $httpOnly = true, $sameSite = null)
    {
        if (is_null($name)) {
            return app('cookie');
        }

        return app('cookie')->make($name, $value, $minutes, $path, $domain, $secure, $httpOnly, $sameSite);
    }
}

/**
 * Get the CSRF token value.
 *
 * @return string
 */
if (!function_exists('csrf_token')) {
    function csrf_token()
    {
        return session()->token();
    }
}

/**
 * Generate a CSRF token form field.
 *
 * @return string
 */
if (!function_exists('csrf_field')) {
    function csrf_field()
    {
        return '<input type="hidden" name="_token" value="' . csrf_token() . '">';
    }
}

/**
 * Generate a form field to spoof the HTTP verb.
 *
 * @param string $method
 * @return string
 */
if (!function_exists('method_field')) {
    function method_field($method)
    {
        return '<input type="hidden" name="_method" value="' . $method . '">';
    }
}

/**
 * Retrieve old input values.
 *
 * @param string|null $key
 * @param mixed $default
 * @return mixed
 */
if (!function_exists('old')) {
    function old($key = null, $default = null)
    {
        return session()->getOldInput($key, $default);
    }
}

/**
 * Flash data to the session.
 *
 * @param string $key
 * @param mixed $value
 * @return mixed
 */
if (!function_exists('flash')) {
    function flash($key, $value)
    {
        return session()->flash($key, $value);
    }
}

/**
 * Redirect back to the previous page.
 *
 * @return RedirectResponse
 */
if (!function_exists('back')) {
    function back()
    {
        $referer = $_SERVER['HTTP_REFERER'] ?? '/';
        return redirect($referer);
    }
}

/**
 * Create a redirect response.
 *
 * @param string|null $to
 * @param int $status
 * @param array $headers
 * @param bool|null $secure
 * @return Redirector|RedirectResponse
 */
if (!function_exists('redirect')) {
    function redirect($to = null, $status = 302, $headers = [], $secure = null): Redirector | RedirectResponse
    {
        if (is_null($to)) {
            return app('redirect');
        }

        return app('redirect')->to($to, $status, $headers);
    }
}

/**
 * Get the current request instance or an input item from the request.
 *
 * @param  array|string|null  $key
 * @param  mixed  $default
 * @return \Arpon\Http\Request|mixed
 */
if (!function_exists('request')) {
    function request($key = null, $default = null)
    {
        if (is_null($key)) {
            return app('request');
        }

        if (is_array($key)) {
            return app('request')->merge($key);
        }

        return app('request')->input($key, $default);
    }
}

/**
 * Generate a URL.
 *
 * @param string|null $path
 * @param array $parameters
 * @param bool|null $secure
 * @return UrlGenerator|string
 */
if (!function_exists('url')) {
    function url($path = null, $parameters = [], $secure = null)
    {
        if (is_null($path)) {
            return app('url');
        }

        return app('url')->to($path, $parameters, $secure);
    }
}

/**
 * Generate a URL to a named route.
 *
 * @param string $name
 * @param array $parameters
 * @param bool $absolute
 * @return string
 */
if (!function_exists('route')) {
    function route($name, $parameters = [], $absolute = true)
    {
        // Allow shorthand route('name', true) -> route('name', [], true)
        if (is_bool($parameters)) {
            $absolute = $parameters;
            $parameters = [];
        }

        return app('url')->route($name, $parameters, $absolute);
    }
}

/**
 * Generate a URL to a controller action.
 *
 * @param string $action
 * @param array $parameters
 * @return string
 */
if (!function_exists('action')) {
    function action($action, $parameters = [])
    {
        return app('url')->action($action, $parameters);
    }
}

/**
 * Create a validator instance or get the validator factory.
 *
 * @param array $data
 * @param array $rules
 * @param array $messages
 * @param array $customAttributes
 * @return \Arpon\Validation\Factory|\Arpon\Validation\Validator
 */
if (!function_exists('validator')) {
    function validator(array $data = [], array $rules = [], array $messages = [], array $customAttributes = [])
    {
        $factory = app('validator');

        if (func_num_args() === 0) {
            return $factory;
        }

        return $factory->make($data, $rules, $messages, $customAttributes);
    }
}

/**
 * Validate data against rules.
 *
 * @param array $data
 * @param array $rules
 * @param array $messages
 * @param array $customAttributes
 * @return array
 * @throws \Arpon\Validation\ValidationException
 */
if (!function_exists('validate')) {
    function validate(array $data, array $rules, array $messages = [], array $customAttributes = [])
    {
        return app('validator')->validate($data, $rules, $messages, $customAttributes);
    }
}

/**
 * Translate a message.
 *
 * @param string $key
 * @param array $replace
 * @param string|null $locale
 * @return string
 */
if (!function_exists('__')) {
    function __($key, $replace = [], $locale = null)
    {
        // Simple translation function - returns the key as-is for now
        // Could be extended to use a translation service
        if (empty($replace)) {
            return $key;
        }

        // Replace placeholders like :name with actual values
        foreach ($replace as $search => $value) {
            $key = str_replace(':' . $search, $value, $key);
        }

        return $key;
    }
}

/**
 * Get the auth manager or a specific guard.
 *
 * @param string|null $guard
 * @return AuthManager|SessionGuard
 */
if (!function_exists('auth')) {
    function auth($guard = null): AuthManager | SessionGuard
    {
        if (is_null($guard)) {
            return app('auth');
        }

        return app('auth')->guard($guard);
    }
}

/**
 * Translate a message (alias of __).
 *
 * @param string $key
 * @param array $replace
 * @param string|null $locale
 * @return string
 */
if (!function_exists('trans')) {
    function trans($key, $replace = [], $locale = null)
    {
        return __($key, $replace, $locale);
    }
}

/**
 * Generate a random string.
 *
 * @param int $length
 * @return string
 */
if (!function_exists('str_random')) {
    function str_random($length = 16)
    {
        $string = '';
        
        while (($len = strlen($string)) < $length) {
            $size = $length - $len;
            $bytes = random_bytes($size);
            $string .= substr(str_replace(['/', '+', '='], '', base64_encode($bytes)), 0, $size);
        }
        
        return $string;
    }
}

/**
 * Abort the application with an HTTP status code.
 *
 * @param int $code
 * @param string $message
 * @param array $headers
 * @return void
 */
if (!function_exists('abort')) {
    function abort($code, $message = '', array $headers = [], $exception = null)
    {
        throw new \Arpon\Http\Exceptions\HttpException($code, $message, null, $headers);
    }
}

/**
 * Dump the passed variables and end the script.
 *
 * @param mixed ...$args
 * @return void
 */
if (!function_exists('dd')) {
    /**
     * @throws ReflectionException
     */
    #[NoReturn]
    function dd(...$args): void
    {
        foreach ($args as $arg) {
            // If it's a Model instance, convert to array and hide sensitive data
            if (is_object($arg) && method_exists($arg, 'toArray')) {
                $data = $arg->toArray();
                
                // Get hidden fields if available
                $hidden = [];
                if (property_exists($arg, 'hidden')) {
                    $reflection = new ReflectionClass($arg);
                    $property = $reflection->getProperty('hidden');
                    $hidden = $property->getValue($arg);
                }
                
                // Remove hidden fields
                foreach ($hidden as $field) {
                    if (isset($data[$field])) {
                        $data[$field] = '***HIDDEN***';
                    }
                }
                
                echo '<pre>';
                var_dump($data);
                echo '</pre>';
            } else {
                echo '<pre>';
                var_dump($arg);
                echo '</pre>';
            }
        }
        
        die(1);
    }
}

/**
 * Format a variable for dumping with HTML and collapsible support.
 *
 * @param mixed $var
 * @param int $indent
 * @param int $maxDepth
 * @param int $currentDepth
 * @return string
 */
if (!function_exists('formatDumpHTML')) {
    /**
     * @throws ReflectionException
     */
    function formatDumpHTML($var, $indent = 0, $maxDepth = 10, $currentDepth = 0): string
    {
        static $idCounter = 0;
        $spaces = str_repeat('  ', $indent);
        
        if ($currentDepth > $maxDepth) {
            return htmlspecialchars($spaces . '... (max depth reached)');
        }
        
        if (is_null($var)) {
            return '<span class="dd-null">null</span>';
        }
        
        if (is_bool($var)) {
            return '<span class="dd-boolean">' . ($var ? 'true' : 'false') . '</span>';
        }
        
        if (is_string($var)) {
            return '<span class="dd-string">"' . htmlspecialchars($var) . '"</span>';
        }
        
        if (is_numeric($var)) {
            return '<span class="dd-number">' . $var . '</span>';
        }
        
        if (is_array($var)) {
            if (empty($var)) {
                return '<span class="dd-bracket">[]</span>';
            }
            
            $id = 'collapse-' . (++$idCounter);
            $count = count($var);
            
            $output = '<span class="dd-collapsible" onclick="toggleCollapse(this)">';
            $output .= '<span class="dd-collapsible-arrow">▼</span>';
            $output .= '<span class="dd-bracket">[</span> <span class="dd-null">' . $count . ' ' . ($count === 1 ? 'item' : 'items') . '</span>';
            $output .= '</span>';
            $output .= '<div class="dd-collapsible-content">';
            
            foreach ($var as $key => $value) {
                $output .= "\n" . htmlspecialchars($spaces . '  ');
                $output .= '<span class="dd-key">';
                $output .= is_string($key) ? '"' . htmlspecialchars($key) . '"' : $key;
                $output .= '</span>';
                $output .= ' <span class="dd-arrow">=&gt;</span> ';
                $output .= formatDumpHTML($value, $indent + 1, $maxDepth, $currentDepth + 1);
            }
            
            $output .= "\n" . htmlspecialchars($spaces) . '</div>';
            $output .= htmlspecialchars($spaces) . '<span class="dd-bracket">]</span>';
            
            return $output;
        }
        
        if (is_object($var)) {
            $className = get_class($var);
            $reflection = new ReflectionClass($var);
            $properties = [];
            
            // Get all properties (public, protected, private)
            foreach ($reflection->getProperties() as $prop) {
                $properties[$prop->getName()] = $prop->getValue($var);
            }
            
            if (empty($properties)) {
                return '<span class="dd-class">' . htmlspecialchars($className) . '</span> <span class="dd-bracket">{}</span>';
            }
            
            $id = 'collapse-' . (++$idCounter);
            $count = count($properties);
            
            $output = '<span class="dd-collapsible" onclick="toggleCollapse(this)">';
            $output .= '<span class="dd-collapsible-arrow">▼</span>';
            $output .= '<span class="dd-class">' . htmlspecialchars($className) . '</span> ';
            $output .= '<span class="dd-bracket">{</span> <span class="dd-null">' . $count . ' ' . ($count === 1 ? 'property' : 'properties') . '</span>';
            $output .= '</span>';
            $output .= '<div class="dd-collapsible-content">';
            
            foreach ($properties as $key => $value) {
                $output .= "\n" . htmlspecialchars($spaces . '  ');
                $output .= '<span class="dd-key">' . htmlspecialchars($key) . '</span>';
                $output .= '<span class="dd-arrow">:</span> ';
                $output .= formatDumpHTML($value, $indent + 1, $maxDepth, $currentDepth + 1);
            }
            
            $output .= "\n" . htmlspecialchars($spaces) . '</div>';
            $output .= htmlspecialchars($spaces) . '<span class="dd-bracket">}</span>';
            
            return $output;
        }
        
        return '<span class="dd-null">unknown type</span>';
    }
}

/**
 * Format a variable for dumping.
 *
 * @param mixed $var
 * @param int $indent
 * @param int $maxDepth
 * @param int $currentDepth
 * @return string
 */
if (!function_exists('formatDump')) {
    function formatDump($var, $indent = 0, $maxDepth = 10, $currentDepth = 0): string
    {
        $spaces = str_repeat('  ', $indent);
        
        if ($currentDepth > $maxDepth) {
            return $spaces . '... (max depth reached)';
        }
        
        if (is_null($var)) {
            return 'null';
        }
        
        if (is_bool($var)) {
            return $var ? 'true' : 'false';
        }
        
        if (is_string($var)) {
            return '"' . $var . '"';
        }
        
        if (is_numeric($var)) {
            return (string) $var;
        }
        
        if (is_array($var)) {
            if (empty($var)) {
                return '[]';
            }
            
            $output = "[\n";
            foreach ($var as $key => $value) {
                $output .= $spaces . '  ' . (is_string($key) ? '"' . $key . '"' : $key) . ' => ';
                $output .= formatDump($value, $indent + 1, $maxDepth, $currentDepth + 1) . "\n";
            }
            $output .= $spaces . ']';
            return $output;
        }
        
        if (is_object($var)) {
            $className = get_class($var);
            $reflection = new ReflectionClass($var);
            $properties = [];
            
            // Get all properties (public, protected, private)
            foreach ($reflection->getProperties() as $prop) {
                $prop->setAccessible(true);
                $properties[$prop->getName()] = $prop->getValue($var);
            }
            
            if (empty($properties)) {
                return $className . ' {}';
            }
            
            $output = $className . " {\n";
            foreach ($properties as $key => $value) {
                $output .= $spaces . '  ' . $key . ': ';
                $output .= formatDump($value, $indent + 1, $maxDepth, $currentDepth + 1) . "\n";
            }
            $output .= $spaces . '}';
            return $output;
        }
        
        return 'unknown type';
    }
}

/**
 * Dump the passed variables (without dying).
 *
 * @param mixed ...$args
 * @return void
 */
if (!function_exists('dump')) {
    function dump(...$args)
    {
        echo '<pre style="background: #2d3748; color: #e2e8f0; padding: 15px; border-radius: 6px; margin: 10px 0; overflow-x: auto; font-family: monospace; font-size: 13px;">';
        foreach ($args as $arg) {
            echo htmlspecialchars(formatDump($arg, 0)) . "\n\n";
        }
        echo '</pre>';
    }
}

/**
 * Get the Str helper instance.
 *
 * @return \Arpon\Support\Str
 */
if (!function_exists('str')) {
    function str()
    {
        return new \Arpon\Support\Str();
    }
}

/**
 * Get the Carbon instance for date/time manipulation.
 *
 * @param string|null $datetime
 * @param \DateTimeZone|string|null $timezone
 * @return \Arpon\Support\Carbon
 */
if (!function_exists('carbon')) {
    function carbon($datetime = null, $timezone = null)
    {
        return new \Arpon\Support\Carbon($datetime, $timezone);
    }
}

/**
 * Get the current date/time as Carbon instance.
 *
 * @param \DateTimeZone|string|null $timezone
 * @return \Arpon\Support\Carbon
 */
if (!function_exists('now')) {
    function now($timezone = null)
    {
        return \Arpon\Support\Carbon::now($timezone);
    }
}

/**
 * Get today's date as Carbon instance.
 *
 * @param \DateTimeZone|string|null $timezone
 * @return \Arpon\Support\Carbon
 */
if (!function_exists('today')) {
    function today($timezone = null)
    {
        return \Arpon\Support\Carbon::today($timezone);
    }
}

/**
 * Escape HTML special characters.
 *
 * @param  string  $value
 * @param  bool  $doubleEncode
 * @return string
 */
if (!function_exists('e')) {
    function e($value, $doubleEncode = true)
    {
        return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8', $doubleEncode);
    }
}

/**
 * Send an email using the mailer.
 *
 * @param string|\Arpon\Contracts\Mail\Mailable $mailable
 * @param \Closure|string|null $callback
 * @return void
 */
if (!function_exists('email')) {
    function email($mailable, $callback = null)
    {
        if ($mailable instanceof \Arpon\Contracts\Mail\Mailable) {
            return app('mailer')->send($mailable);
        }

        if (is_string($mailable)) {
            return app('mailer')->html($mailable, $callback ?: function () {});
        }

        throw new \InvalidArgumentException('Invalid mailable type');
    }
}

/**
 * Hash the given value.
 *
 * @param  string  $value
 * @param  array  $options
 * @return string
 */
if (!function_exists('bcrypt')) {
    function bcrypt($value, $options = [])
    {
        return app('hash')->make($value, $options);
    }
}

/**
 * Authorize a given action for the current user.
 *
 * @param  string  $ability
 * @param  mixed  $arguments
 * @return bool
 *
 * @throws \Exception
 */
if (!function_exists('authorize')) {
    function authorize($ability, $arguments = [])
    {
        return app('gate')->authorize($ability, $arguments);
    }
}

/**
 * Encrypt the given value.
 *
 * @param  mixed  $value
 * @param  bool  $serialize
 * @return string
 */
if (!function_exists('encrypt')) {
    function encrypt($value, $serialize = true)
    {
        return app('encrypter')->encrypt($value, $serialize);
    }
}

/**
 * Decrypt the given value.
 *
 * @param  string  $payload
 * @param  bool  $unserialize
 * @return mixed
 */
if (!function_exists('decrypt')) {
    function decrypt($payload, $unserialize = true)
    {
        return app('encrypter')->decrypt($payload, $unserialize);
    }
}

/**
 * Add an element to an array if it doesn't exist.
 *
 * @param  array  $array
 * @param  string|int  $key
 * @param  mixed  $value
 * @return array
 */
if (!function_exists('array_add')) {
    function array_add($array, $key, $value)
    {
        if (is_null($key)) {
            return $array;
        }
        
        if (!isset($array[$key])) {
            $array[$key] = $value;
        }
        
        return $array;
    }
}

/**
 * Call the given Closure with the given value then return the value.
 *
 * @param  mixed  $value
 * @param  callable|null  $callback
 * @return mixed
 */
if (!function_exists('tap')) {
    function tap($value, $callback = null)
    {
        if (is_null($callback)) {
            return new class($value) {
                private $value;
                
                public function __construct($value)
                {
                    $this->value = $value;
                }
                
                public function __call($method, $parameters)
                {
                    $result = $this->value->{$method}(...$parameters);
                    
                    return new static($this->value);
                }
            };
        }
        
        $callback($value);
        
        return $value;
    }
}

/**
 * Get the cache manager or a specific value from the cache.
 *
 * @param  string|null  $key
 * @param  mixed  $default
 * @return mixed|CacheManager
 */
if (!function_exists('cache')) {
    function cache($key = null, $default = null)
    {
        if (is_null($key)) {
            return app('cache');
        }

        if (is_array($key)) {
            return app('cache')->put($key, $default);
        }

        return app('cache')->get($key, $default);
    }
}

/**
 * Determine if the current request URI matches a pattern.
 *
 * @param  mixed  ...$patterns
 * @return bool
 */
if (!function_exists('request_is')) {
    function request_is(...$patterns)
    {
        return app('request')->is(...$patterns);
    }
}
