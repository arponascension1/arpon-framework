<?php

namespace Arpon\Validation\Rules;

use Arpon\Contracts\Validation\Rule;

class Ip implements Rule
{
    public function passes($attribute, $value)
    {
        if (!is_string($value)) {
            return false;
        }

        return filter_var($value, FILTER_VALIDATE_IP) !== false;
    }

    public function message()
    {
        return 'The :attribute must be a valid IP address.';
    }
}
