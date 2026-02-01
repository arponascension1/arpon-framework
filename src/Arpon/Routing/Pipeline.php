<?php

namespace Arpon\Routing;

use Closure;

class Pipeline
{
    protected $container;
    protected $passable;
    protected $pipes = [];
    protected $method = 'handle';

    public function __construct($container)
    {
        $this->container = $container;
    }

    public function send($passable)
    {
        $this->passable = $passable;
        return $this;
    }

    public function through($pipes)
    {
        $this->pipes = is_array($pipes) ? $pipes : [$pipes];
        return $this;
    }

    public function via($method)
    {
        $this->method = $method;
        return $this;
    }

    public function then(Closure $destination)
    {
        $pipeline = array_reduce(
            array_reverse($this->pipes),
            $this->carry(),
            $this->prepareDestination($destination)
        );

        return $pipeline($this->passable);
    }

    protected function prepareDestination(Closure $destination)
    {
        return function ($passable) use ($destination) {
            try {
                return $destination($passable);
            } catch (\Exception $e) {
                return $this->handleException($passable, $e);
            }
        };
    }

    protected function carry()
    {
        return function ($stack, $pipe) {
            return function ($passable) use ($stack, $pipe) {
                try {
                    if (is_callable($pipe)) {
                        return $pipe($passable, $stack);
                    } elseif (!is_object($pipe)) {
                        $pipe = $this->container->make($pipe);
                    }

                    $carry = method_exists($pipe, $this->method)
                        ? [$pipe, $this->method]
                        : [$pipe, '__invoke'];

                    return $this->container->call($carry, [$passable, $stack]);
                } catch (\Exception $e) {
                    return $this->handleException($passable, $e);
                }
            };
        };
    }

    protected function handleException($passable, \Exception $e)
    {
        throw $e;
    }
}
