<?php

namespace Arpon\Validation\Rules;

use Arpon\Contracts\Validation\Rule;

class Url implements Rule
{
    public function passes($attribute, $value)
    {
        if (!is_string($value)) {
            return false;
        }

        return filter_var($value, FILTER_VALIDATE_URL) !== false;
    }

    public function message()
    {
        return 'The :attribute format is invalid.';
    }
}
