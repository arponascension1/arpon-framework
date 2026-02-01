<?php

namespace Arpon\Validation\Rules;

use Arpon\Contracts\Validation\Rule;

class Alpha implements Rule
{
    public function passes($attribute, $value)
    {
        if (!is_string($value)) {
            return false;
        }

        return preg_match('/^[\pL\pM]+$/u', $value) === 1;
    }

    public function message()
    {
        return 'The :attribute may only contain letters.';
    }
}
