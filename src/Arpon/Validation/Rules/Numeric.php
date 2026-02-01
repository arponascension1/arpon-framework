<?php

namespace Arpon\Validation\Rules;

use Arpon\Contracts\Validation\Rule;

class Numeric implements Rule
{
    public function passes($attribute, $value)
    {
        return is_numeric($value);
    }

    public function message()
    {
        return 'The :attribute must be a number.';
    }
}
