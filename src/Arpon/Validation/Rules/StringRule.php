<?php

namespace Arpon\Validation\Rules;

use Arpon\Contracts\Validation\Rule;

class StringRule implements Rule
{
    public function passes($attribute, $value)
    {
        return is_string($value);
    }

    public function message()
    {
        return 'The :attribute must be a string.';
    }
}
