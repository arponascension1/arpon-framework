<?php

namespace Arpon\Validation\Rules;

use Arpon\Contracts\Validation\Rule;

class Mimes implements Rule
{
    protected $mimes;

    public function __construct(...$mimes)
    {
        $this->mimes = is_array($mimes[0]) ? $mimes[0] : $mimes;
    }

    public function passes($attribute, $value)
    {
        // Handle UploadedFile objects
        if ($value instanceof \Arpon\Http\UploadedFile) {
            if (!$value->isValid()) {
                return false;
            }
            
            $extension = $value->extension();
            return in_array($extension, $this->mimes);
        }

        // Handle raw array format (legacy)
        if (!is_array($value) || !isset($value['tmp_name']) || !isset($value['name'])) {
            return false;
        }

        $extension = strtolower(pathinfo($value['name'], PATHINFO_EXTENSION));
        return in_array($extension, $this->mimes);
    }

    public function message()
    {
        return 'The :attribute must be a file of type: :values.';
    }

    public function getMimes()
    {
        return $this->mimes;
    }
}
