<?php

namespace Arpon\View;

use Arpon\Foundation\Application;

class Factory
{
    protected $app;
    protected $shared = [];
    protected $namespaces = [];
    protected $paths = [];
    protected $compiled;

    public function __construct(Application $app, array $paths = [], $compiled = null)
    {
        $this->app = $app;
        $this->paths = $paths;
        $this->compiled = $compiled;
    }

    public function make($view, $data = [])
    {
        $data = array_merge($this->shared, $data);

        return new View($this->app, $view, $data, $this->namespaces, $this->paths, $this->compiled);
    }

    public function addNamespace($namespace, $hint)
    {
        $this->namespaces[$namespace] = $hint;
    }

    public function share($key, $value = null)
    {
        if (is_array($key)) {
            $this->shared = array_merge($this->shared, $key);
        } else {
            $this->shared[$key] = $value;
        }
    }

    public function render($view, $data = [])
    {
        return $this->make($view, $data)->render();
    }

    public function getPaths()
    {
        return $this->paths;
    }

    public function getCompiledPath()
    {
        return $this->compiled;
    }
}
