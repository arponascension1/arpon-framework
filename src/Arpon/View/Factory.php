<?php

namespace Arpon\View;

use InvalidArgumentException;
use Arpon\Filesystem\Filesystem;

class Factory
{
    protected Filesystem $files;
    protected string $viewPath;
    protected array $data = [];

    public function __construct(Filesystem $files, string $viewPath)
    {
        $this->files = $files;
        $this->viewPath = rtrim($viewPath, '/');
    }

    public function make(string $view, array $data = []): View
    {
        $path = $this->findView($view);

        return new View($this, $path, $data);
    }

    public function makePartial(string $view, array $data = []): View
    {
        return $this->make($view, $data);
    }

    protected function findView(string $view): string
    {
        $path = $this->viewPath . '/' . str_replace('.', '/', $view) . '.php';

        if (! $this->files->exists($path)) {
            throw new InvalidArgumentException("View [{$view}] not found at [{$path}].");
        }

        return $path;
    }

    public function share(string $key, $value): void
    {
        $this->data[$key] = $value;
    }

    public function getShared(): array
    {
        return $this->data;
    }
}
