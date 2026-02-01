<?php

namespace Arpon\Validation\Rules;

use Arpon\Contracts\Validation\Rule;

class Ipv6 implements Rule
{
    public function passes($attribute, $value)
    {
        if (!is_string($value)) {
            return false;
        }

        return filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false;
    }

    public function message()
    {
        return 'The :attribute must be a valid IPv6 address.';
    }
}
