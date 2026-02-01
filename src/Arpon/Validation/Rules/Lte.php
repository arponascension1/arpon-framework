<?php

namespace Arpon\Validation\Rules;

use Arpon\Contracts\Validation\Rule;

class Lte implements Rule
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

        return $value <= $this->value;
    }

    public function message()
    {
        return 'The :attribute must be less than or equal :value.';
    }

    public function getValue()
    {
        return $this->value;
    }
}
