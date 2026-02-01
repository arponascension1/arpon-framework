<?php

namespace Arpon\Validation\Rules;

use Arpon\Contracts\Validation\Rule;
use Arpon\Http\UploadedFile;

class Image implements Rule
{
    public function passes($attribute, $value)
    {
        // Handle UploadedFile objects
        if ($value instanceof UploadedFile) {
            if (!$value->isValid()) {
                return false;
            }
            
            $imageInfo = @getimagesize($value->getRealPath());
            return $imageInfo !== false;
        }

        // Handle raw array format (legacy)
        if (!is_array($value) || !isset($value['tmp_name'])) {
            return false;
        }

        if (!file_exists($value['tmp_name'])) {
            return false;
        }

        $imageInfo = @getimagesize($value['tmp_name']);
        return $imageInfo !== false;
    }

    public function message()
    {
        return 'The :attribute must be an image.';
    }
}
