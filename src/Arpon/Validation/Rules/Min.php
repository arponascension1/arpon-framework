<?php

namespace Arpon\Validation\Rules;

use Arpon\Contracts\Validation\Rule;

class Min implements Rule
{
    protected $min;

    public function __construct($min)
    {
        $this->min = $min;
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        if (is_string($value)) {
            return mb_strlen($value) >= $this->min;
        }

        if (is_numeric($value)) {
            return $value >= $this->min;
        }

        if (is_array($value)) {
            return count($value) >= $this->min;
        }

        return false;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'The :attribute must be at least :min.';
    }

    /**
     * Get the min value for message replacement.
     *
     * @return int
     */
    public function getMin()
    {
        return $this->min;
    }
}
