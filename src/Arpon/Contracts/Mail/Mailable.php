<?php

namespace Arpon\Contracts\Mail;

interface Mailable
{
    public function send($mailer);
    
    public function to($address, $name = null);
    
    public function cc($address, $name = null);
    
    public function bcc($address, $name = null);
    
    public function from($address, $name = null);
    
    public function subject($subject);
    
    public function replyTo($address, $name = null);
    
    public function attach($file, array $options = []);
    
    public function attachData($data, $name, array $options = []);
    
    public function with($key, $value = null);
    
    public function view($view, $data = []);
    
    public function text($view, $data = []);
    
    public function html($html);
    
    public function raw($text);
}
