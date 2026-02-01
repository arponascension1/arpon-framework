<?php

namespace Arpon\Validation\Rules;

use Arpon\Contracts\Validation\Rule;

class Uppercase implements Rule
{
    public function passes($attribute, $value)
    {
        if (!is_string($value)) {
            return false;
        }

        return $value === mb_strtoupper($value, 'UTF-8');
    }

    public function message()
    {
        return 'The :attribute must be uppercase.';
    }
}
