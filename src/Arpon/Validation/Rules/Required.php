<?php

namespace Arpon\Validation\Rules;

use Arpon\Contracts\Validation\ImplicitRule;

class Required implements ImplicitRule
{
    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        if (is_null($value)) {
            return false;
        }

        // Handle UploadedFile objects
        if ($value instanceof \Arpon\Http\UploadedFile) {
            return $value->isValid();
        }

        if (is_string($value) && trim($value) === '') {
            return false;
        }

        if (is_array($value) && count($value) < 1) {
            return false;
        }

        return true;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'The :attribute field is required.';
    }
}
