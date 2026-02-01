<?php

namespace Arpon\Validation\Rules;

use Arpon\Contracts\Validation\Rule;

class Multiple implements Rule
{
    protected $value;

    public function __construct($value)
    {
        $this->value = $value;
    }

    public function passes($attribute, $value)
    {
        if (!is_numeric($value) || !is_numeric($this->value)) {
            return false;
        }

        return fmod($value, $this->value) == 0;
    }

    public function message()
    {
        return 'The :attribute must be a multiple of :value.';
    }

    public function getValue()
    {
        return $this->value;
    }
}
