<?php

namespace Arpon\Validation\Rules;

use Arpon\Contracts\Validation\ImplicitRule;

class Present implements ImplicitRule
{
    public function passes($attribute, $value)
    {
        // Present rule just checks if key exists, not if it has value
        // This is checked at validator level by checking array_key_exists
        return true;
    }

    public function message()
    {
        return 'The :attribute field must be present.';
    }
}
