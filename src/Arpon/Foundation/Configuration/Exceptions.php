<?php

namespace Arpon\Foundation\Configuration;

class Exceptions
{
    protected array $handlers = [];
    protected array $renderables = [];

    public function handler(\Throwable $exception, callable $handler): static
    {
        $this->handlers[get_class($exception)] = $handler;
        return $this;
    }

    public function renderable(callable $callback): static
    {
        $this->renderables[] = $callback;
        return $this;
    }

    public function getHandlers(): array
    {
        return $this->handlers;
    }

    public function getRenderables(): array
    {
        return $this->renderables;
    }
}
