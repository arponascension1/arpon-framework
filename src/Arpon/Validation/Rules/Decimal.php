<?php

namespace Arpon\Validation\Rules;

use Arpon\Contracts\Validation\Rule;

class Decimal implements Rule
{
    protected $min;
    protected $max;

    public function __construct($min = null, $max = null)
    {
        $this->min = $min;
        $this->max = $max;
    }

    public function passes($attribute, $value)
    {
        if (!is_numeric($value)) {
            return false;
        }

        $decimalPlaces = 0;
        if (strpos($value, '.') !== false) {
            $decimalPlaces = strlen(substr(strrchr($value, "."), 1));
        }

        if ($this->min !== null && $decimalPlaces < $this->min) {
            return false;
        }

        if ($this->max !== null && $decimalPlaces > $this->max) {
            return false;
        }

        return true;
    }

    public function message()
    {
        if ($this->min !== null && $this->max !== null) {
            return 'The :attribute must have between :min and :max decimal places.';
        }

        if ($this->min !== null) {
            return 'The :attribute must have at least :min decimal places.';
        }

        if ($this->max !== null) {
            return 'The :attribute must not have more than :max decimal places.';
        }

        return 'The :attribute must be a decimal number.';
    }
}
