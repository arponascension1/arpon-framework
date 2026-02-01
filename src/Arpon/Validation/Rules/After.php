<?php

namespace Arpon\Validation\Rules;

use Arpon\Contracts\Validation\Rule;

class After implements Rule
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

        return $valueTimestamp > $dateTimestamp;
    }

    public function message()
    {
        return 'The :attribute must be a date after :date.';
    }

    public function getDate()
    {
        return $this->date;
    }
}
