<?php

namespace Arpon\Validation\Rules;

use Arpon\Contracts\Validation\Rule;
use DateTime;

class DateFormat implements Rule
{
    protected $format;

    public function __construct($format)
    {
        $this->format = $format;
    }

    public function passes($attribute, $value)
    {
        if (!is_string($value) && !is_numeric($value)) {
            return false;
        }

        $date = DateTime::createFromFormat($this->format, $value);
        return $date && $date->format($this->format) === $value;
    }

    public function message()
    {
        return 'The :attribute does not match the format :format.';
    }

    public function getFormat()
    {
        return $this->format;
    }
}
