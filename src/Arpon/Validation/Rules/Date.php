<?php

namespace Arpon\Validation\Rules;

use Arpon\Contracts\Validation\Rule;
use DateTime;

class Date implements Rule
{
    public function passes($attribute, $value)
    {
        if ($value instanceof DateTime) {
            return true;
        }

        if (!is_string($value) && !is_numeric($value)) {
            return false;
        }

        return strtotime($value) !== false;
    }

    public function message()
    {
        return 'The :attribute is not a valid date.';
    }
}
