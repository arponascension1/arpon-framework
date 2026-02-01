<?php

namespace Arpon\Validation\Rules;

use Arpon\Contracts\Validation\Rule;
use Arpon\Http\UploadedFile;

class File implements Rule
{
    public function passes($attribute, $value)
    {
        // Handle UploadedFile objects
        if ($value instanceof UploadedFile) {
            return $value->isValid();
        }

        // Handle raw array format (legacy)
        if (!is_array($value)) {
            return false;
        }

        return isset($value['tmp_name']) 
            && isset($value['error']) 
            && $value['error'] === UPLOAD_ERR_OK
            && file_exists($value['tmp_name']);
    }

    public function message()
    {
        return 'The :attribute must be a file.';
    }
}
