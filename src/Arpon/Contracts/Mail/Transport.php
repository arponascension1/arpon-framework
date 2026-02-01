<?php

namespace Arpon\Contracts\Mail;

interface Transport
{
    public function send($message);
    
    public function createMessage();
}
