<?php

namespace Arpon\Contracts\Foundation;

interface Application
{
    public function basePath($path = '');
    public function configPath($path = '');
    public function publicPath($path = '');
    public function storagePath($path = '');
    public function bootstrapPath($path = '');
    public function make($abstract, array $parameters = []);
    public function bound($abstract);
    public function singleton($abstract, $concrete = null);
    public function instance($abstract, $instance);
    public function register($provider, $force = false);
    public function bootstrapWith(array $bootstrappers);
}
