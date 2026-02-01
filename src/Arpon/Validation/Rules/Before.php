<?php

namespace Arpon\Validation\Rules;

use Arpon\Contracts\Validation\Rule;

class Before implements Rule
{
    protected $date;

    public function __construct($date)
    {
        $this->date = $date;
    }

    public function passes($attribute, $value)
    {
        $valueTimestamp = strtotime($value);
        $dateTimestamp = strtotime($this->date);

        if ($valueTimestamp === false || $dateTimestamp === false) {
            return false;
        }

        return $valueTimestamp < $dateTimestamp;
    }

    public function message()
    {
        return 'The :attribute must be a date before :date.';
    }

    public function getDate()
    {
        return $this->date;
    }
}
