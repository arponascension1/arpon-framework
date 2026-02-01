<?php

namespace Arpon\Validation\Rules;

use Arpon\Contracts\Validation\Rule;

class Max implements Rule
{
    protected $max;

    public function __construct($max)
    {
        $this->max = $max;
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
        // Handle UploadedFile objects (size in kilobytes)
        if ($value instanceof \Arpon\Http\UploadedFile) {
            return ($value->getSize() / 1024) <= $this->max;
        }

        if (is_string($value)) {
            $len = mb_strlen($value);
            return $len <= $this->max;
        }

        if (is_numeric($value)) {
            return $value <= $this->max;
        }

        if (is_array($value)) {
            // Handle raw file array (size in bytes, convert to KB)
            if (isset($value['size'])) {
                return ($value['size'] / 1024) <= $this->max;
            }
            return count($value) <= $this->max;
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
        return 'The :attribute may not be greater than :max.';
    }

    /**
     * Get the max value for message replacement.
     *
     * @return int
     */
    public function getMax()
    {
        return $this->max;
    }
}
