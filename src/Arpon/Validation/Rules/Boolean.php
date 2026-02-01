<?php

namespace Arpon\Validation\Rules;

use Arpon\Contracts\Validation\Rule;

class Boolean implements Rule
{
    public function passes($attribute, $value)
    {
        $acceptable = [true, false, 0, 1, '0', '1'];
        return in_array($value, $acceptable, true);
    }

    public function message()
    {
        return 'The :attribute field must be true or false.';
    }
}
