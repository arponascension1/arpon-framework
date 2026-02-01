<?php

namespace Arpon\Validation\Rules;

use Arpon\Contracts\Validation\DataAwareRule;
use Arpon\Contracts\Validation\Rule;

class Same implements Rule, DataAwareRule
{
    protected $field;
    protected $data = [];

    public function __construct($field)
    {
        $this->field = $field;
    }

    public function setData($data)
    {
        $this->data = $data;
        return $this;
    }

    public function passes($attribute, $value)
    {
        return isset($this->data[$this->field]) 
            && $value === $this->data[$this->field];
    }

    public function message()
    {
        return 'The :attribute and :other must match.';
    }

    public function getField()
    {
        return $this->field;
    }
}
