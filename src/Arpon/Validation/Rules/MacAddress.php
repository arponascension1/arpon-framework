<?php

namespace Arpon\Validation\Rules;

use Arpon\Contracts\Validation\Rule;

class MacAddress implements Rule
{
    public function passes($attribute, $value)
    {
        if (!is_string($value)) {
            return false;
        }

        return preg_match('/^([0-9A-Fa-f]{2}[:-]){5}([0-9A-Fa-f]{2})$/', $value) === 1;
    }

    public function message()
    {
        return 'The :attribute must be a valid MAC address.';
    }
}
