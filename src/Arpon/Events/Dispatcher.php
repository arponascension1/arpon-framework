<?php

namespace Arpon\Events;

use Arpon\Contracts\Events\Dispatcher as DispatcherContract;

class Dispatcher implements DispatcherContract
{
    protected $listeners = [];
    protected $wildcards = [];
    protected $app;

    public function __construct($app = null)
    {
        $this->app = $app;
    }

    public function listen($events, $listener)
    {
        foreach ((array) $events as $event) {
            if (str_contains($event, '*')) {
                $this->wildcards[$event][] = $listener;
            } else {
                $this->listeners[$event][] = $listener;
            }
        }
    }

    public function hasListeners($eventName)
    {
        if (isset($this->listeners[$eventName])) {
            return true;
        }

        foreach ($this->wildcards as $key => $listeners) {
            if ($this->wildcardMatches($eventName, $key)) {
                return true;
            }
        }

        return false;
    }

    public function dispatch($event, $payload = [], $halt = false)
    {
        if (is_object($event)) {
            list($event, $payload) = [get_class($event), [$event]];
        }

        $responses = [];

        foreach ($this->getListeners($event) as $listener) {
            $response = $listener($event, $payload);

            if ($halt && !is_null($response)) {
                return $response;
            }

            if ($response === false) {
                break;
            }

            $responses[] = $response;
        }

        return $halt ? null : $responses;
    }

    public function until($event, $payload = [])
    {
        return $this->dispatch($event, $payload, true);
    }

    public function push($event, $payload = [])
    {
        $this->listen($event, function () use ($event, $payload) {
            $this->dispatch($event, $payload);
        });
    }

    public function flush($event)
    {
        $this->dispatch($event);
    }

    public function forget($event)
    {
        unset($this->listeners[$event]);
        unset($this->wildcards[$event]);
    }

    public function forgetPushed()
    {
        foreach ($this->listeners as $key => $value) {
            if (str_ends_with($key, '_pushed')) {
                $this->forget($key);
            }
        }
    }

    protected function getListeners($eventName)
    {
        $listeners = $this->listeners[$eventName] ?? [];

        $listeners = array_merge($listeners, $this->getWildcardListeners($eventName));

        return class_exists($eventName) ? $this->prepareClassListeners($listeners) : $listeners;
    }

    protected function getWildcardListeners($eventName)
    {
        $wildcards = [];

        foreach ($this->wildcards as $key => $listeners) {
            if ($this->wildcardMatches($eventName, $key)) {
                $wildcards = array_merge($wildcards, $listeners);
            }
        }

        return $wildcards;
    }

    protected function wildcardMatches($eventName, $pattern)
    {
        $pattern = preg_quote($pattern, '#');
        $pattern = str_replace('\*', '.*', $pattern);

        return (bool) preg_match('#^' . $pattern . '\z#', $eventName);
    }

    protected function prepareClassListeners(array $listeners)
    {
        return array_map(function ($listener) {
            if (is_string($listener)) {
                return $this->createClassCallable($listener);
            }

            return $listener;
        }, $listeners);
    }

    protected function createClassCallable($listener)
    {
        return function ($event, $payload) use ($listener) {
            $callable = [$this->app->make($listener), 'handle'];

            return call_user_func_array($callable, $payload);
        };
    }

    /**
     * Register an event subscriber with the dispatcher.
     *
     * @param  object|string  $subscriber
     * @return void
     */
    public function subscribe($subscriber)
    {
        if (is_string($subscriber)) {
            $subscriber = $this->app ? $this->app->make($subscriber) : new $subscriber;
        }

        $subscriber->subscribe($this);
    }

    /**
     * Get all registered listeners.
     *
     * @return array
     */
    public function getListenersAll()
    {
        return $this->listeners;
    }
}
