<?php

namespace Arpon\Mail\Events;

class MessageSending
{
    public $message;

    public function __construct($message)
    {
        $this->message = $message;
    }
}
