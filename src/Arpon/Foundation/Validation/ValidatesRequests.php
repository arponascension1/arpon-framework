<?php

namespace Arpon\Foundation\Validation;

use Arpon\Http\Request;
use Arpon\Validation\ValidationException;

trait ValidatesRequests
{
    public function validate(Request $request, array $rules, array $messages = [], array $attributes = [])
    {
        return validator($request->all(), $rules, $messages, $attributes)->validate();
    }

    public function validateWithBag($errorBag, Request $request, array $rules, array $messages = [], array $attributes = [])
    {
        try {
            return $this->validate($request, $rules, $messages, $attributes);
        } catch (ValidationException $e) {
            throw $e;
        }
    }
}
