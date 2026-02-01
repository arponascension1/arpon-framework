<?php

namespace Arpon\Validation\Rules;

use Arpon\Contracts\Validation\Rule;

class NotIn implements Rule
{
    protected $values;

    public function __construct(array $values)
    {
        $this->values = $values;
    }

    public function passes($attribute, $value)
    {
        return !in_array($value, $this->values, true);
    }

    public function message()
    {
        return 'The selected :attribute is invalid.';
    }
}
