<?php

namespace Arpon\Foundation;

use Arpon\Config\ConfigServiceProvider;
use Arpon\Contracts\Foundation\Application as ApplicationContract;
use Arpon\Database\DatabaseServiceProvider;
use Arpon\Events\EventServiceProvider;
use Arpon\Exceptions\GeneralExceptionHandler;
use Arpon\Exceptions\RouteNotFoundExceptionHandler;
use Arpon\Exceptions\ValidationExceptionHandler;
use Arpon\Foundation\Configuration\Exceptions;
use Arpon\Foundation\Configuration\Middleware;
use Arpon\Log\LogServiceProvider;
use Arpon\Routing\Exceptions\RouteNotFoundException;
use Arpon\Routing\RoutingServiceProvider;
use Arpon\Support\Facades\DB as DBFacade;
use Arpon\Support\Facades\Request as RequestFacade;
use Arpon\Support\Facades\Route as RouteFacade;
use Arpon\Support\Facades\Schema as SchemaFacade;
use Arpon\Support\Facades\View as ViewFacade;
use Arpon\Validation\ValidationException;
use Arpon\View\ViewServiceProvider;
use ArrayAccess;

class Application implements ApplicationContract, ArrayAccess
{
    protected static $instance;
    protected string $basePath;
    protected bool $hasBeenBootstrapped = false;
    protected array $serviceProviders = [];
    protected array $loadedProviders = [];
    protected bool $booted = false;
    protected array $deferredServices = [];
    protected array $config = [];

    public static function configure(string $basePath): static
    {
        return new static($basePath);
    }

    public function withRouting(array $routes): static
    {
        $this->config['routes'] = $routes;
        return $this;
    }

    public function withMiddleware(callable $callback): static
    {
        $middleware = new Middleware();
        $callback($middleware);

        // Register core middleware - Prepend StartSession to web group
        $groups = $middleware->getMiddlewareGroups();
        $webGroup = $groups['web'] ?? [];
        array_unshift($webGroup, \Arpon\Http\Middleware\StartSession::class);
        $middleware->web($webGroup);

        $this->config['middleware'] = $middleware;
        return $this;
    }

    public function withExceptions(callable $callback): static
    {
        $exceptions = new Exceptions();
        $callback($exceptions);
        $this->config['exceptions'] = $exceptions;
        return $this;
    }

    public function create(): static
    {
        $this->bootstrapWith([
            \Arpon\Foundation\Bootstrap\LoadEnvironmentVariables::class,
            \Arpon\Foundation\Bootstrap\LoadConfiguration::class,
            \Arpon\Foundation\Bootstrap\RegisterProviders::class,
            \Arpon\Foundation\Bootstrap\BootProviders::class,
        ]);
        return $this;
    }

    public function handleRequest($request)
    {
        try {
            $this->instance('request', $request);

            // Set up facades before loading routes
            \Arpon\Support\Facades\Facade::setFacadeApplication($this);

            $router = $this['router'];

            // Pass middleware configuration to router
            $middlewareConfig = $this->config['middleware'] ?? null;
            
            if ($middlewareConfig) {
                $router->setMiddlewareGroups($middlewareConfig->getMiddlewareGroups());
                $router->setRouteMiddleware($middlewareConfig->getMiddleware());
            }

            // Load routes dynamically
            $this->loadRoutes();

            // Sync routes to URL generator for named route support
            $this->syncRoutesToUrlGenerator();

            return $router->dispatch($request);
        } catch (ValidationException $e) {
            $handler = new ValidationExceptionHandler($this, $this->config['exceptions'] ?? new Exceptions());
            return $handler->handle($e, $request);
                
        } catch (RouteNotFoundException $e) {
            $handler = new RouteNotFoundExceptionHandler($this, $this->config['exceptions'] ?? new Exceptions());
            return $handler->handle($e, $request);
        } catch (\Throwable $e) {
            $handler = new GeneralExceptionHandler($this, $this->config['exceptions'] ?? new Exceptions());
            return $handler->handle($e, $request);
        }
    }

    public function __construct($basePath = null)
    {
        // Always set the base path if provided
        if ($basePath) {
            $this->basePath = realpath($basePath) ?: $basePath;
            $this->basePath = rtrim($this->basePath, '\\/');
            $this->bindPathsInContainer();
        }
        
        // Only set singleton if not already set
        if (static::$instance === null) {
            static::$instance = $this;
        }

        // Helper functions are now autoloaded via Composer's autoload.files.

        $this->registerBaseBindings();
        $this->registerBaseServiceProviders();
    }

    public static function getInstance()
    {
        return static::$instance;
    }

    public function setBasePath($basePath)
    {
        // Resolve the path (handle ../)
        $this->basePath = realpath($basePath) ?: $basePath;
        $this->basePath = rtrim($this->basePath, '\\/');
        $this->bindPathsInContainer();
        return $this;
    }

    protected function bindPathsInContainer()
    {
        $this->instance('path', $this->path());
        $this->instance('path.base', $this->basePath());
        $this->instance('path.config', $this->configPath());
        $this->instance('path.public', $this->publicPath());
        $this->instance('path.storage', $this->storagePath());
        $this->instance('path.bootstrap', $this->bootstrapPath());
    }

    public function path($path = '')
    {
        return $this->basePath . (DIRECTORY_SEPARATOR . $path);
    }

    public function basePath($path = '')
    {
        return $this->basePath . (DIRECTORY_SEPARATOR . $path);
    }

    public function configPath($path = '')
    {
        return $this->basePath . DIRECTORY_SEPARATOR . 'config' . (DIRECTORY_SEPARATOR . $path);
    }

    public function publicPath($path = '')
    {
        return $this->basePath . DIRECTORY_SEPARATOR . 'public' . (DIRECTORY_SEPARATOR . $path);
    }

    public function storagePath($path = '')
    {
        return $this->basePath . DIRECTORY_SEPARATOR . 'storage' . (DIRECTORY_SEPARATOR . $path);
    }

    public function databasePath($path = '')
    {
        return $this->basePath . DIRECTORY_SEPARATOR . 'database' . (DIRECTORY_SEPARATOR . $path);
    }

    public function resourcePath($path = '')
    {
        return $this->basePath . DIRECTORY_SEPARATOR . 'resources' . (DIRECTORY_SEPARATOR . $path);
    }

    public function bootstrapPath($path = '')
    {
        return $this->basePath . DIRECTORY_SEPARATOR . 'bootstrap' . (DIRECTORY_SEPARATOR . $path);
    }

    /**
     * Get the version of the application.
     *
     * @return string
     */
    public function version()
    {
        return '1.0.0';
    }

    protected function registerBaseBindings()
    {
        static::$instance = $this;
        $this->instance('app', $this);
        $this->instance('Arpon\Foundation\Application', $this);

        // Set Facade application instance
        \Arpon\Support\Facades\Facade::setFacadeApplication($this);

        // Register Filesystem
        $this->singleton('files', function ($app) {
            return new \Arpon\Support\Filesystem();
        });

        if (!class_exists('Route', false)) {
            class_alias(RouteFacade::class, 'Route');
        }

        if (!class_exists('Request', false)) {
            class_alias(RequestFacade::class, 'Request');
        }

        if (!class_exists('View', false)) {
            class_alias(ViewFacade::class, 'View');
        }

        if (!class_exists('DB', false)) {
            class_alias(DBFacade::class, 'DB');
        }

        if (!class_exists('Schema', false)) {
            class_alias(SchemaFacade::class, 'Schema');
        }

        if (!class_exists('Mail', false)) {
            class_alias(\Arpon\Support\Facades\Mail::class, 'Mail');
        }

        if (!class_exists('Gate', false)) {
            class_alias(\Arpon\Support\Facades\Gate::class, 'Gate');
        }

        if (!class_exists('Notification', false)) {
            class_alias(\Arpon\Support\Facades\Notification::class, 'Notification');
        }

        if (!class_exists('Cache', false)) {
            class_alias(\Arpon\Support\Facades\Cache::class, 'Cache');
        }
    }

    protected function registerBaseServiceProviders()
    {
        $this->register(new EventServiceProvider($this));
        $this->register(new LogServiceProvider($this));
        $this->register(new RoutingServiceProvider($this));
        $this->register(new ViewServiceProvider($this));
        $this->register(new ConfigServiceProvider($this));
        $this->register(new DatabaseServiceProvider($this));
        $this->register(new \Arpon\Console\ConsoleServiceProvider($this));
        $this->register(new \Arpon\Session\SessionServiceProvider($this));
        $this->register(new \Arpon\Auth\AuthServiceProvider($this));
        $this->register(new \Arpon\Validation\ValidationServiceProvider($this));
        $this->register(new \Arpon\Encryption\EncryptionServiceProvider($this));
        $this->register(new \Arpon\Filesystem\FilesystemServiceProvider($this));
        $this->register(new \Arpon\Cache\CacheServiceProvider($this));
        $this->register(new \Arpon\Mail\MailServiceProvider($this));
        $this->register(new \Arpon\Notifications\NotificationServiceProvider($this));

    }

    public function register($provider, $force = false)
    {
        if (($registered = $this->getProvider($provider)) && ! $force) {
            return $registered;
        }

        if (is_string($provider)) {
            $provider = new $provider($this);
        }

        $provider->register();

        if (property_exists($provider, 'bindings')) {
            foreach ($provider->bindings as $key => $value) {
                $this->bind($key, $value);
            }
        }

        if (property_exists($provider, 'singletons')) {
            foreach ($provider->singletons as $key => $value) {
                $this->singleton($key, $value);
            }
        }

        $this->markAsRegistered($provider);

        if ($this->hasBeenBootstrapped) {
            $this->bootProvider($provider);
        }

        return $provider;
    }

    protected function markAsRegistered($provider)
    {
        $this->serviceProviders[] = $provider;
        $this->loadedProviders[get_class($provider)] = true;
    }

    protected function bootProvider($provider)
    {
        if (method_exists($provider, 'boot')) {
            return $this->call([$provider, 'boot']);
        }
    }

    public function getProvider($provider)
    {
        return array_values($this->getProviders($provider))[0] ?? null;
    }

    public function getProviders($provider)
    {
        $name = is_string($provider) ? $provider : get_class($provider);

        return array_filter($this->serviceProviders, function ($p) use ($name) {
            return $p instanceof $name;
        });
    }

    public function bootstrapWith(array $bootstrappers)
    {
        $this->hasBeenBootstrapped = true;
        $this->booted = true;

        foreach ($bootstrappers as $bootstrapper) {
            $this['events']->dispatch('bootstrapping: ' . $bootstrapper, [$this]);

            $this->make($bootstrapper)->bootstrap($this);

            $this['events']->dispatch('bootstrapped: ' . $bootstrapper, [$this]);
        }

        // Apply application configuration after loading
        $this->applyApplicationConfiguration();
    }

    protected function applyApplicationConfiguration()
    {
        $config = $this['config']->get('app');

        if ($config) {
            if (isset($config['timezone'])) {
                date_default_timezone_set($config['timezone']);
            }

            mb_internal_encoding('UTF-8');

            if (isset($config['locale'])) {
                $this->instance('config.locale', $config['locale']);
                setlocale(LC_ALL, $config['locale']);
            }

            if (isset($config['fallback_locale'])) {
                $this->instance('config.fallback_locale', $config['fallback_locale']);
            }

            if (isset($config['faker_locale'])) {
                $this->instance('config.faker_locale', $config['faker_locale']);
            }

            if (isset($config['debug'])) {
                if ($config['debug']) {
                    error_reporting(E_ALL);
                    ini_set('display_errors', '1');
                } else {
                    error_reporting(0);
                    ini_set('display_errors', '0');
                }
            }

            if (isset($config['name'])) {
                $this->instance('config.name', $config['name']);
            }

            if (isset($config['url'])) {
                $this->instance('config.url', $config['url']);
            }
        }
    }

    /**
     * Get the application name.
     *
     * @return string
     */
    public function name()
    {
        return $this['config']->get('app.name', 'Arpon');
    }

    /**
     * Get the application URL.
     *
     * @return string
     */
    public function url()
    {
        return $this['config']->get('app.url', 'http://localhost');
    }

    /**
     * Get or check the current application environment.
     *
     * @param  string|array  $environments
     * @return string|bool
     */
    public function environment(...$environments)
    {
        $env = $this['config']->get('app.env', 'production');

        if (count($environments) > 0) {
            $patterns = is_array($environments[0]) ? $environments[0] : $environments;

            return in_array($env, $patterns);
        }

        return $env;
    }

    /**
     * Determine if the application is in the local environment.
     *
     * @return bool
     */
    public function isLocal()
    {
        return $this->environment('local');
    }

    /**
     * Determine if the application is in the production environment.
     *
     * @return bool
     */
    public function isProduction()
    {
        return $this->environment('production');
    }

    /**
     * Determine if the application is in debug mode.
     *
     * @return bool
     */
    public function isDebug()
    {
        return (bool) $this['config']->get('app.debug', false);
    }

    /**
     * Check if the application has been booted.
     *
     * @return bool
     */
    public function isBooted()
    {
        return $this->booted;
    }

    public function make($abstract, array $parameters = [])
    {
        // Always use the singleton instance for make() to ensure consistency
        if (static::$instance && $this !== static::$instance) {
            return static::$instance->make($abstract, $parameters);
        }
        

        
        // If we're making a Console Kernel, ensure it gets the singleton instance
        if ($abstract === \Arpon\Console\Kernel::class) {
            return new \Arpon\Console\Kernel(static::$instance);
        }
        
        return $this->resolve($abstract, $parameters);
    }

    public function bound($abstract)
    {
        return isset($this->bindings[$abstract]) || isset($this->instances[$abstract]) || $this->isAlias($abstract);
    }

    public function isAlias($name)
    {
        return isset($this->aliases[$name]);
    }

    protected function getAlias($abstract)
    {
        return isset($this->aliases[$abstract]) ? $this->aliases[$abstract] : $abstract;
    }

    public function bind($abstract, $concrete = null, $shared = false)
    {
        $this->bindings[$abstract] = ['concrete' => $concrete, 'shared' => $shared];
    }

    public function singleton($abstract, $concrete = null)
    {
        $this->bind($abstract, $concrete, true);
    }

    public function instance($abstract, $instance)
    {
        unset($this->aliases[$abstract]);

        $this->instances[$abstract] = $instance;
    }

    public function flush($abstract)
    {
        unset($this->bindings[$abstract]);
        unset($this->instances[$abstract]);
        unset($this->resolved[$abstract]);
    }

    public function resolve($abstract, $parameters = [])
    {
        $abstract = $this->getAlias($abstract);

        if (isset($this->instances[$abstract])) {
            return $this->instances[$abstract];
        }

        $concrete = $this->getConcrete($abstract);

        if ($this->isShared($abstract) && isset($this->instances[$abstract])) {
            return $this->instances[$abstract];
        }

        $object = $this->build($concrete, $parameters);

        if ($this->isShared($abstract)) {
            $this->instances[$abstract] = $object;
        }

        $this->resolved[$abstract] = true;

        return $object;
    }

    protected function getConcrete($abstract)
    {
        if (isset($this->bindings[$abstract])) {
            return $this->bindings[$abstract]['concrete'];
        }

        return $abstract;
    }

    protected function isShared($abstract)
    {
        return isset($this->instances[$abstract]) || (isset($this->bindings[$abstract]['shared']) && $this->bindings[$abstract]['shared'] === true);
    }

    public function build($concrete, array $parameters = [])
    {
        if ($concrete instanceof \Closure) {
            return $concrete($this, $parameters);
        }

        $reflector = new \ReflectionClass($concrete);

        if (!$reflector->isInstantiable()) {
            throw new \Exception("Target [{$concrete}] is not instantiable.");
        }

        $constructor = $reflector->getConstructor();

        if (is_null($constructor)) {
            return new $concrete;
        }

        $dependencies = $constructor->getParameters();
        $instances = $this->getDependencies($dependencies, $parameters);

        return $reflector->newInstanceArgs($instances);
    }

    protected function getDependencies(array $parameters, array $primitives = [])
    {
        $dependencies = [];

        foreach ($parameters as $parameter) {
            $type = $parameter->getType();

            if ($type && $type->isBuiltin()) {
                if (array_key_exists($parameter->name, $primitives)) {
                    $dependencies[] = $primitives[$parameter->name];
                } elseif ($parameter->isDefaultValueAvailable()) {
                    $dependencies[] = $parameter->getDefaultValue();
                } else {
                    $dependencies[] = null;
                }
            } elseif ($type) {
                $typeName = $type->getName();

                // Check if we have a pre-resolved instance (e.g., from route model binding)
                if (array_key_exists($parameter->name, $primitives)) {
                    $value = $primitives[$parameter->name];

                    if ($value instanceof $typeName) {
                        $dependencies[] = $value;
                    } elseif (is_subclass_of($typeName, 'Arpon\Database\Eloquent\Model') && (is_string($value) || is_int($value))) {
                        $model = $this->make($typeName);
                        $resolved = $model->resolveRouteBinding($value);

                        if (!$resolved) {
                            throw (new \Arpon\Database\Eloquent\ModelNotFoundException)->setModel($typeName, [$value]);
                        }

                        $dependencies[] = $resolved;
                    } else {
                        $dependencies[] = $value;
                    }
                } else {
                    $dependencies[] = $this->make($typeName);
                }
            } else {
                if (array_key_exists($parameter->name, $primitives)) {
                    $dependencies[] = $primitives[$parameter->name];
                } elseif ($parameter->isDefaultValueAvailable()) {
                    $dependencies[] = $parameter->getDefaultValue();
                } else {
                    $dependencies[] = null;
                }
            }
        }

        return $dependencies;
    }

    public function call($callback, array $parameters = [], $defaultMethod = null)
    {
        if ($callback instanceof \Closure) {
            $reflection = new \ReflectionFunction($callback);
            $dependencies = $this->getDependencies($reflection->getParameters(), $parameters);
            return $callback(...$dependencies);
        }

        if (is_string($callback)) {
            return $this->callClass($callback, $parameters, $defaultMethod);
        }

        if (is_array($callback)) {
            return $this->callMethod($callback, $parameters);
        }

        throw new \Exception('Invalid callback provided.');
    }

    protected function callClass($callback, array $parameters, $defaultMethod)
    {
        [$class, $method] = $this->parseClassCallback($callback, $defaultMethod);

        return $this->call([$this->make($class), $method], $parameters);
    }

    protected function parseClassCallback($callback, $defaultMethod)
    {
        if (str_contains($callback, '@')) {
            return explode('@', $callback);
        }

        return [$callback, $defaultMethod ?: '__invoke'];
    }

    protected function callMethod($callback, array $parameters)
    {
        if (!is_array($callback) || count($callback) !== 2 || !isset($callback[0], $callback[1])) {
            throw new \Exception('Invalid callback format. Expected [instance, method].');
        }

        [$instance, $method] = $callback;

        if (is_string($instance)) {
            $instance = $this->make($instance);
        }

        $reflection = new \ReflectionMethod($instance, $method);
        $args = [];

        $currentRequest = $this->make('request');
        $route = $currentRequest->route();
        
        $routeValues = $route ? array_values($route->parameters()) : [];
        $routeValueIndex = 0;

        foreach ($reflection->getParameters() as $index => $param) {
            $type = $param->getType();
            $name = $param->getName();
            $className = ($type && !$type->isBuiltin()) ? $type->getName() : null;

            // 1. Try to find a match in the provided $parameters (by name or index)
            if (isset($parameters[$name])) {
                $value = $parameters[$name];
                if (!$className || ($value instanceof $className) || !$this->isEloquentModel($className)) {
                    $args[] = $value;
                    continue;
                }
            } elseif (isset($parameters[$index])) {
                $value = $parameters[$index];
                if (!$className || ($value instanceof $className) || !$this->isEloquentModel($className)) {
                    $args[] = $value;
                    continue;
                }
            }

            // 2. Handle Request / FormRequest
            if ($className) {
                if (is_subclass_of($className, 'Arpon\Foundation\Http\FormRequest')) {
                    $formRequest = $className::createFrom($currentRequest);
                    $formRequest->validateResolved();
                    $args[] = $formRequest;
                    continue;
                }
                
                if ($className === 'Arpon\Http\Request') {
                    $args[] = $currentRequest;
                    continue;
                }

                // 3. Handle Eloquent Model Binding
                if ($this->isEloquentModel($className)) {
                    $value = $parameters[$name] ?? (isset($routeValues[$routeValueIndex]) ? $routeValues[$routeValueIndex++] : null);

                    if ($value !== null) {
                        if ($value instanceof $className) {
                            $args[] = $value;
                        } else {
                            try {
                                $modelInstance = new $className;
                                $model = $modelInstance->resolveRouteBinding($value);
                                if (!$model) {
                                    abort(404);
                                }
                                $args[] = $model;
                                if ($route) {
                                    $route->setParameter($name, $model);
                                }
                            } catch (\Exception $e) {
                                abort(404);
                            }
                        }
                        continue;
                    }
                }

                // 4. Resolve other classes from container
                if ($this->bound($className) || (class_exists($className) && (new \ReflectionClass($className))->isInstantiable())) {
                    $args[] = $this->make($className);
                    continue;
                }
            }

            // 5. Handle Positional Route Parameters or Defaults
            if (isset($routeValues[$routeValueIndex])) {
                $args[] = $routeValues[$routeValueIndex++];
            } elseif ($param->isDefaultValueAvailable()) {
                $args[] = $param->getDefaultValue();
            } elseif ($param->isVariadic()) {
                $args[] = [];
            } else {
                throw new \Exception("Required parameter '{$name}' is missing");
            }
        }

        return $reflection->invokeArgs($instance, $args);
    }

    public function offsetExists($offset): bool
    {
        return $this->bound($offset);
    }

    public function offsetGet($offset): mixed
    {
        return $this->make($offset);
    }

    public function offsetSet($offset, $value): void
    {
        $this->bind($offset, $value instanceof \Closure ? $value : fn() => $value);
    }

    public function offsetUnset($offset): void
    {
        unset($this->bindings[$offset]);
        unset($this->instances[$offset]);
        unset($this->resolved[$offset]);
    }

    public function __get($key)
    {
        return $this[$key];
    }

    public function __set($key, $value)
    {
        $this[$key] = $value;
    }

    public function alias($abstract, $alias)
    {
        $this->aliases[$alias] = $abstract;
        return $this;
    }

    public function get($id)
    {
        return $this->make($id);
    }

    public function has($id)
    {
        return $this->bound($id);
    }

    public function getLoadedProviders()
    {
        return $this->loadedProviders;
    }

    /**
     * Load all configured route files dynamically.
     *
     * @return void
     */
    protected function loadRoutes()
    {
        if (!isset($this->config['routes']) || !is_array($this->config['routes'])) {
            return;
        }

        // Register health check route if configured
        if (isset($this->config['routes']['health'])) {
            $this->registerHealthCheckRoute($this->config['routes']['health']);
        }

        $excludeKeys = ['health', 'commands']; // Keys to skip (not route files)

        foreach ($this->config['routes'] as $key => $path) {
            // Skip non-route configuration keys
            if (in_array($key, $excludeKeys)) {
                continue;
            }

            // Load the route file if it exists
            if (is_string($path) && file_exists($path)) {
                require $path;
            }
        }
    }

    /**
     * Register the health check route.
     *
     * @param string $uri
     * @return void
     */
    protected function registerHealthCheckRoute($uri)
    {
        $router = $this->make('router');
        
        $router->get($uri, function() {
            return response()->json([
                'status' => 'ok',
                'timestamp' => date('Y-m-d H:i:s'),
            ]);
        });
    }

    protected function syncRoutesToUrlGenerator()
    {
        $router = $this->make('router');
        $url = $this->make('url');
        
        $namedRoutes = [];
        foreach ($router->getRoutes()->getRoutes() as $route) {
            $name = $route->getName();
            if ($name) {
                $namedRoutes[$name] = $route->getUri();
            }
        }
        
        $url->setRoutes($namedRoutes);
    }

    protected $bindings = [];
    protected $instances = [];
    protected $aliases = [];
    protected $resolved = [];

    /**
     * Handle an Artisan console command.
     *
     * @param  \Symfony\Component\Console\Input\ArgvInput  $input
     * @return int
     */
    public function handleCommand($input)
    {
        // Set Facade Application
        \Arpon\Support\Facades\Facade::setFacadeApplication($this);

        // Make the console kernel
        $kernel = $this->make(\Arpon\Console\Kernel::class);
        
        // Create output
        $output = new \Symfony\Component\Console\Output\ConsoleOutput;
        
        // Handle the command
        $status = $kernel->handle($input, $output);
        
        // Terminate the kernel
        $kernel->terminate($input, $status);
        
        return $status;
    }

    protected function isEloquentModel($class)
    {
        if (!class_exists($class)) {
            return false;
        }
        
        $reflection = new \ReflectionClass($class);
        
        // Check if it extends Model
        while ($parent = $reflection->getParentClass()) {
            if ($parent->getName() === 'Arpon\\Database\\Eloquent\\Model') {
                return true;
            }
            $reflection = $parent;
        }
        
        return false;
    }
}
