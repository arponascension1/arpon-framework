<?php

namespace Arpon\Validation\Rules;

use Arpon\Contracts\Validation\ImplicitRule;

class Declined implements ImplicitRule
{
    public function passes($attribute, $value)
    {
        $acceptable = ['no', 'off', '0', 0, false, 'false'];
        return in_array($value, $acceptable, true);
    }

    public function message()
    {
        return 'The :attribute must be declined.';
    }
}
