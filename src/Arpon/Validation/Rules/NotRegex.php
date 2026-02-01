<?php

namespace Arpon\Validation\Rules;

use Arpon\Contracts\Validation\Rule;

class NotRegex implements Rule
{
    protected $pattern;

    public function __construct($pattern)
    {
        $this->pattern = $pattern;
    }

    public function passes($attribute, $value)
    {
        if (!is_string($value) && !is_numeric($value)) {
            return false;
        }

        return preg_match($this->pattern, $value) === 0;
    }

    public function message()
    {
        return 'The :attribute format is invalid.';
    }
}
