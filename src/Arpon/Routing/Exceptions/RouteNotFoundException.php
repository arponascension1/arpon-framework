<?php

namespace Arpon\Routing\Exceptions;

class RouteNotFoundException extends \Exception
{
    protected $path;
    protected $method;

    public function __construct(string $path, string $method)
    {
        $this->path = $path;
        $this->method = $method;

        parent::__construct("Route not found for: {$path} ({$method})", 404);
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getMethod(): string
    {
        return $this->method;
    }
}
