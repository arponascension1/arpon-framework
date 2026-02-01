<?php

namespace Arpon\Validation\Rules;

use Arpon\Contracts\Validation\Rule;

class Between implements Rule
{
    protected $min;
    protected $max;

    public function __construct($min, $max)
    {
        $this->min = $min;
        $this->max = $max;
    }

    public function passes($attribute, $value)
    {
        $size = $this->getSize($value);
        return $size >= $this->min && $size <= $this->max;
    }

    protected function getSize($value)
    {
        if (is_string($value)) {
            return mb_strlen($value);
        }

        if (is_numeric($value)) {
            return $value;
        }

        if (is_array($value)) {
            return count($value);
        }

        return 0;
    }

    public function message()
    {
        return 'The :attribute must be between :min and :max.';
    }

    public function getMin()
    {
        return $this->min;
    }

    public function getMax()
    {
        return $this->max;
    }
}
