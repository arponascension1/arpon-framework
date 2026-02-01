<?php

namespace Arpon\Contracts\Mail;

interface Mailer
{
    public function send($mailable);
    
    public function to($address, $name = null);
    
    public function cc($address, $name = null);
    
    public function bcc($address, $name = null);
    
    public function subject($subject);
    
    public function from($address, $name = null);
    
    public function replyTo($address, $name = null);
    
    public function attach($file, array $options = []);
    
    public function attachData($data, $name, array $options = []);
    
    public function raw($text, $callback);
    
    public function html($html, $callback);
}
