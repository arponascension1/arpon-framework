<?php

namespace Arpon\Contracts\Cache;

interface Factory
{
    /**
     * Get a cache store instance by name.
     *
     * @param  string|null  $name
     * @return \Arpon\Contracts\Cache\Repository
     */
    public function store($name = null);
}
