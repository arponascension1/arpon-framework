<?php

namespace Arpon\Validation\Rules;

use Arpon\Contracts\Validation\Rule;

class Nullable implements Rule
{
    public function passes($attribute, $value)
    {
        return true; // Always passes, just marks field as nullable
    }

    public function message()
    {
        return '';
    }
}
