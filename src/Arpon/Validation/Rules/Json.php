<?php

namespace Arpon\Validation\Rules;

use Arpon\Contracts\Validation\Rule;

class Json implements Rule
{
    public function passes($attribute, $value)
    {
        if (!is_string($value)) {
            return false;
        }

        json_decode($value);
        return json_last_error() === JSON_ERROR_NONE;
    }

    public function message()
    {
        return 'The :attribute must be a valid JSON string.';
    }
}
