<?php

namespace Arpon\Validation\Rules;

use Arpon\Contracts\Validation\DataAwareRule;
use Arpon\Contracts\Validation\Rule;

class Confirmed implements Rule, DataAwareRule
{
    protected $data = [];

    public function setData($data)
    {
        $this->data = $data;
        return $this;
    }

    public function passes($attribute, $value)
    {
        $confirmationAttribute = $attribute . '_confirmation';
        
        return isset($this->data[$confirmationAttribute]) 
            && $value === $this->data[$confirmationAttribute];
    }

    public function message()
    {
        return 'The :attribute confirmation does not match.';
    }
}
