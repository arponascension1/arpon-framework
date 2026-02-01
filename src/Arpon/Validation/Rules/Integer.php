<?php

namespace Arpon\Validation\Rules;

use Arpon\Contracts\Validation\Rule;

class Integer implements Rule
{
    public function passes($attribute, $value)
    {
        return filter_var($value, FILTER_VALIDATE_INT) !== false;
    }

    public function message()
    {
        return 'The :attribute must be an integer.';
    }
}
