<?php

namespace Arpon\Validation\Rules;

use Arpon\Contracts\Validation\ImplicitRule;

class Accepted implements ImplicitRule
{
    public function passes($attribute, $value)
    {
        $acceptable = ['yes', 'on', '1', 1, true, 'true'];
        return in_array($value, $acceptable, true);
    }

    public function message()
    {
        return 'The :attribute must be accepted.';
    }
}
