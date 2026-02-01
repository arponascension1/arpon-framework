<?php

namespace Arpon\Validation\Rules;

use Arpon\Contracts\Validation\Rule;

class AlphaDash implements Rule
{
    public function passes($attribute, $value)
    {
        if (!is_string($value) && !is_numeric($value)) {
            return false;
        }

        return preg_match('/^[\pL\pM\pN_-]+$/u', $value) === 1;
    }

    public function message()
    {
        return 'The :attribute may only contain letters, numbers, dashes and underscores.';
    }
}
