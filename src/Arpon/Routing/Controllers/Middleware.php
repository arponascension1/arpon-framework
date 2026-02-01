<?php

namespace Arpon\Routing\Controllers;

class Middleware
{
    /**
     * The name of the middleware.
     */
    public string $middleware;

    /**
     * The methods the middleware should apply to.
     */
    public array $only = [];

    /**
     * The methods the middleware should not apply to.
     */
    public array $except = [];

    /**
     * Create a new middleware definition.
     */
    public function __construct(string $middleware, array $only = [], array $except = [])
    {
        $this->middleware = $middleware;
        $this->only = $only;
        $this->except = $except;
    }

    /**
     * Only apply the middleware to the given methods.
     */
    public function only(array|string $methods): static
    {
        $this->only = (array) $methods;
        return $this;
    }

    /**
     * Exclude the middleware from the given methods.
     */
    public function except(array|string $methods): static
    {
        $this->except = (array) $methods;
        return $this;
    }
}
