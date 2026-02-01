<?php

namespace Arpon\Validation;

class Factory
{
    protected $container;
    protected $resolver;

    public function __construct($container = null)
    {
        $this->container = $container;
    }

    public function make(array $data, array $rules, array $messages = [], array $attributes = [])
    {
        $validator = $this->resolve($data, $rules, $messages, $attributes);

        return $validator;
    }

    protected function resolve(array $data, array $rules, array $messages, array $attributes)
    {
        if (is_null($this->resolver)) {
            return new Validator($data, $rules, $messages, $attributes);
        }

        return call_user_func($this->resolver, $data, $rules, $messages, $attributes);
    }

    public function extend($rule, $extension, $message = null)
    {
        Validator::extend($rule, $extension, $message);
    }

    public function extendImplicit($rule, $extension, $message = null)
    {
        Validator::extendImplicit($rule, $extension, $message);
    }

    public function replacer($rule, $replacer)
    {
        Validator::replacer($rule, $replacer);
    }

    public function resolver(callable $resolver)
    {
        $this->resolver = $resolver;

        return $this;
    }

    public function __call($method, $parameters)
    {
        $validator = $this->make(...$parameters);

        return $validator->{$method}();
    }
}
