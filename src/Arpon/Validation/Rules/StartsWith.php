<?php

namespace Arpon\Validation\Rules;

use Arpon\Contracts\Validation\Rule;

class StartsWith implements Rule
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
            if (str_starts_with($value, $needle)) {
                return true;
            }
        }

        return false;
    }

    public function message()
    {
        return 'The :attribute must start with one of the following: :values.';
    }
}
