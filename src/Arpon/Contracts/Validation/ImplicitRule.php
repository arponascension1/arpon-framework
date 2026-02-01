<?php

namespace Arpon\Contracts\Validation;

interface ImplicitRule extends Rule
{
    /**
     * Implicit rules are validated even when the attribute is not present.
     * Examples: Required, RequiredIf, Accepted
     */
}
