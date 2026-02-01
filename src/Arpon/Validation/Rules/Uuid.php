<?php

namespace Arpon\Validation\Rules;

use Arpon\Contracts\Validation\Rule;

class Uuid implements Rule
{
    public function passes($attribute, $value)
    {
        if (!is_string($value)) {
            return false;
        }

        return preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $value) === 1;
    }

    public function message()
    {
        return 'The :attribute must be a valid UUID.';
    }
}
