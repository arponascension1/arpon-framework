<?php

namespace Arpon\Validation\Rules;

use Arpon\Contracts\Validation\Rule;

class EndsWith implements Rule
{
    protected $needles;

    public function __construct(...$needles)
    {
        $this->needles = is_array($needles[0]) ? $needles[0] : $needles;
    }

    public function passes($attribute, $value)
    {
        if (!is_string($value)) {
            return false;
        }

        foreach ($this->needles as $needle) {
            if (str_ends_with($value, $needle)) {
                return true;
            }
        }

        return false;
    }

    public function message()
    {
        return 'The :attribute must end with one of the following: :values.';
    }
}
