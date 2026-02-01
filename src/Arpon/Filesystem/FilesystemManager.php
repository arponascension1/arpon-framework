<?php

namespace Arpon\Filesystem;

class FilesystemManager
{
    protected array $disks = [];
    protected array $config;

    public function __construct(array $config = [])
    {
        $this->config = $config;
    }

    /**
     * Get a filesystem disk instance.
     */
    public function disk(string $name = null): FilesystemAdapter
    {
        $name = $name ?? $this->getDefaultDriver();

        if (!isset($this->disks[$name])) {
            $this->disks[$name] = $this->resolve($name);
        }

        return $this->disks[$name];
    }

    /**
     * Resolve the given disk.
     */
    protected function resolve(string $name): FilesystemAdapter
    {
        $config = $this->getConfig($name);

        if (!$config) {
            throw new \InvalidArgumentException("Disk [{$name}] is not defined.");
        }

        $driver = $config['driver'] ?? 'local';

        if ($driver === 'local') {
            return new FilesystemAdapter($config['root']);
        }

        throw new \InvalidArgumentException("Driver [{$driver}] is not supported.");
    }

    /**
     * Get the disk configuration.
     */
    protected function getConfig(string $name): ?array
    {
        return $this->config['disks'][$name] ?? null;
    }

    /**
     * Get the default driver name.
     */
    protected function getDefaultDriver(): string
    {
        return $this->config['default'] ?? 'local';
    }

    /**
     * Dynamically call the default driver instance.
     */
    public function __call(string $method, array $parameters)
    {
        return $this->disk()->$method(...$parameters);
    }
}
