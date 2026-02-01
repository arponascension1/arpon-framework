<?php

namespace Arpon\Support\Facades;

use Arpon\Foundation\Application;

abstract class Facade
{
    protected static $app;
    protected static $resolvedInstance;

    public static function setFacadeApplication(Application $app)
    {
        static::$app = $app;
    }

    public static function clearResolvedInstances()
    {
        static::$resolvedInstance = [];
    }

    protected static function getFacadeAccessor()
    {
        throw new \Exception('Facade does not implement getFacadeAccessor method.');
    }

    protected static function resolveFacadeInstance($name)
    {
        if (isset(static::$resolvedInstance[$name])) {
            return static::$resolvedInstance[$name];
        }

        if (static::$app) {
            return static::$resolvedInstance[$name] = static::$app->make($name);
        }

        throw new \Exception('Application instance not set.');
    }

    public static function __callStatic($method, $args)
    {
        $instance = static::resolveFacadeInstance(static::getFacadeAccessor());

        if (!$instance) {
            throw new \Exception('Target [' . static::getFacadeAccessor() . '] is not instantiable.');
        }

        return $instance->$method(...$args);
    }
}
