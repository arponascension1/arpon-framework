<?php

namespace Arpon\Support;

class Url
{
    protected $baseUrl;

    public function __construct($baseUrl = 'http://localhost')
    {
        $this->baseUrl = rtrim($baseUrl, '/');
    }

    public function to($path = '', $parameters = [])
    {
        $path = ltrim($path, '/');
        // Ensure parameters are an array before building a query string.
        // If a scalar (boolean/int) is accidentally passed, treat as no parameters
        // to avoid producing query strings like "0=1" from http_build_query.
        if (!empty($parameters) && is_array($parameters)) {
            $path .= '?' . http_build_query($parameters);
        }
        
        return $this->baseUrl . '/' . $path;
    }

    public function asset($path)
    {
        return $this->to('assets/' . ltrim($path, '/'));
    }

    public function route($name, $parameters = [])
    {
        // This would need to be implemented based on your routing system
        return $this->to($name, $parameters);
    }
}
