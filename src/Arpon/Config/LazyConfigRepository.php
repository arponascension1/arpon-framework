<?php

namespace Arpon\Config;

use Arpon\Foundation\Application;
use ArrayAccess;

class LazyConfigRepository implements ArrayAccess
{
    private $app;
    private $repository;
    private $loaded = false;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    private function load()
    {
        if ($this->loaded) {
            return;
        }

        $cachePath = $this->app->bootstrapPath('cache/config.php');

        if (file_exists($cachePath)) {
            $items = require $cachePath;
            $this->repository = new Repository($items);
            $this->loaded = true;
            return;
        }

        // Ensure environment variables are loaded first
        $this->loadEnvironmentVariables();
        
        $configPath = $this->app->configPath();
    // Load configuration files from the config/ directory under the application base path
        $items = [];

        if (is_dir($configPath)) {
            $configFiles = glob($configPath . '/*.php');

            if ($configFiles) {
                foreach ($configFiles as $file) {
                    $key = basename($file, '.php');
                    $config = require $file;

                    if (is_array($config)) {
                        $items[$key] = $config;
                    }
                }
            }
        }

        $this->repository = new Repository($items);
        $this->loaded = true;
    }

    private function loadEnvironmentVariables()
    {
        $envFile = $this->app->basePath('.env');
        
        if (file_exists($envFile)) {
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

            foreach ($lines as $line) {
                $line = trim($line);

                if ($line === '' || strpos($line, '#') === 0) {
                    continue;
                }

                if (strpos($line, 'export ') === 0) {
                    $line = trim(substr($line, 7));
                }

                if (strpos($line, '=') === false) {
                    continue;
                }

                list($key, $value) = explode('=', $line, 2);
                $key = trim($key);

                if ($key === '') {
                    continue;
                }

                $value = ltrim($value);

                if (strlen($value) >= 2) {
                    $first = $value[0];
                    $last = $value[strlen($value) - 1];

                    if (($first === '"' && $last === '"') || ($first === "'" && $last === "'")) {
                        $value = substr($value, 1, -1);
                    }
                }

                // Expand variables like ${APP_NAME}
                if (preg_match_all('/\${([^}]+)}/', $value, $matches)) {
                    foreach ($matches[1] as $index => $varName) {
                        $varValue = $_ENV[$varName] ?? $_SERVER[$varName] ?? getenv($varName) ?: '';
                        $value = str_replace($matches[0][$index], $varValue, $value);
                    }
                }

                if (function_exists('putenv')) {
                    putenv($key . '=' . $value);
                }
                $_ENV[$key] = $value;
                $_SERVER[$key] = $value;
            }
        }
    }

    public function get($key, $default = null)
    {
        $this->load();
        return $this->repository->get($key, $default);
    }

    public function set($key, $value = null)
    {
        $this->load();
        return $this->repository->set($key, $value);
    }

    public function all()
    {
        $this->load();
        return $this->repository->all();
    }

    public function has($key)
    {
        $this->load();
        return $this->repository->has($key);
    }

    public function prepend($key, $value)
    {
        $this->load();
        return $this->repository->prepend($key, $value);
    }

    public function push($key, $value)
    {
        $this->load();
        return $this->repository->push($key, $value);
    }

    public function forget($key)
    {
        $this->load();
        return $this->repository->forget($key);
    }

    public function flush()
    {
        $this->load();
        return $this->repository->flush();
    }

    public function offsetExists($key): bool
    {
        $this->load();
        return $this->repository->has($key);
    }

    public function offsetGet($key): mixed
    {
        $this->load();
        return $this->repository->get($key);
    }

    public function offsetSet($key, $value): void
    {
        $this->load();
        $this->repository->set($key, $value);
    }

    public function offsetUnset($key): void
    {
        $this->load();
        $this->repository->forget($key);
    }
}
