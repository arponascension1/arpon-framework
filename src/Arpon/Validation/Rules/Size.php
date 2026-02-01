<?php

namespace Arpon\Validation\Rules;

use Arpon\Contracts\Validation\Rule;

class Size implements Rule
{
    protected $size;

    public function __construct($size)
    {
        $this->size = $size;
    }

    public function passes($attribute, $value)
    {
        if (is_string($value)) {
            return mb_strlen($value) == $this->size;
        }

        if (is_numeric($value)) {
            return $value == $this->size;
        }

        if (is_array($value)) {
            return count($value) == $this->size;
        }

        return false;
    }

    public function message()
    {
        return 'The :attribute must be :size.';
    }

    public function getSize()
    {
        return $this->size;
    }
}
