<?php

namespace Arpon\Validation\Rules;

use Arpon\Contracts\Validation\DataAwareRule;
use Arpon\Contracts\Validation\ImplicitRule;

class RequiredIf implements ImplicitRule, DataAwareRule
{
    protected $field;
    protected $value;
    protected $data = [];

    public function __construct($field, $value)
    {
        $this->field = $field;
        $this->value = $value;
    }

    public function setData($data)
    {
        $this->data = $data;
        return $this;
    }

    public function passes($attribute, $value)
    {
        if (!isset($this->data[$this->field]) || $this->data[$this->field] != $this->value) {
            return true;
        }

        return !empty($value);
    }

    public function message()
    {
        return 'The :attribute field is required when :other is :value.';
    }
}
