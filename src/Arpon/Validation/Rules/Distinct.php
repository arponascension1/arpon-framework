<?php

namespace Arpon\Validation\Rules;

use Arpon\Contracts\Validation\Rule;

class Distinct implements Rule
{
    protected $strict;
    protected $ignoreCase;

    public function __construct($strict = false, $ignoreCase = false)
    {
        $this->strict = $strict;
        $this->ignoreCase = $ignoreCase;
    }

    public function passes($attribute, $value)
    {
        if (!is_array($value)) {
            return true;
        }

        $values = $value;
        
        if ($this->ignoreCase && !$this->strict) {
            $values = array_map('mb_strtolower', $values);
        }

        $unique = array_unique($values, $this->strict ? SORT_REGULAR : SORT_STRING);
        
        return count($values) === count($unique);
    }

    public function message()
    {
        return 'The :attribute field has duplicate values.';
    }
}
