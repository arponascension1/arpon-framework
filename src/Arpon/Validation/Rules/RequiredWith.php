<?php

namespace Arpon\Validation\Rules;

use Arpon\Contracts\Validation\DataAwareRule;
use Arpon\Contracts\Validation\ImplicitRule;

class RequiredWith implements ImplicitRule, DataAwareRule
{
    protected $fields;
    protected $data = [];

    public function __construct(...$fields)
    {
        $this->fields = is_array($fields[0]) ? $fields[0] : $fields;
    }

    public function setData($data)
    {
        $this->data = $data;
        return $this;
    }

    public function passes($attribute, $value)
    {
        $hasAnyField = false;
        foreach ($this->fields as $field) {
            if (isset($this->data[$field]) && !empty($this->data[$field])) {
                $hasAnyField = true;
                break;
            }
        }

        if (!$hasAnyField) {
            return true;
        }

        return !empty($value);
    }

    public function message()
    {
        return 'The :attribute field is required when :values is present.';
    }
}
