<?php

namespace Arpon\Mail;

use Arpon\Contracts\Mail\Mailable;
use Arpon\Contracts\Mail\Mailer as MailerContract;
use Arpon\Events\Dispatcher;
use Arpon\Mail\Events\MessageSending;
use Arpon\Mail\Events\MessageSent;
use Arpon\View\Factory;

class Mailer implements MailerContract
{
    protected $view;
    protected $transportManager;
    protected $events;
    protected $from;
    protected $replyTo;
    protected $to = [];
    protected $cc = [];
    protected $bcc = [];
    protected $subject;
    protected $attachments = [];
    protected $rawAttachments = [];

    public function __construct(Factory $view, TransportManager $transportManager, Dispatcher $events = null)
    {
        $this->view = $view;
        $this->transportManager = $transportManager;
        $this->events = $events;
    }

    public function send($mailable, $data = [], $callback = null)
    {
        if ($mailable instanceof Mailable) {
            return $this->sendMailable($mailable);
        }

        if (is_string($mailable)) {
            // If second parameter is a callback, treat as simple HTML
            if (is_callable($data)) {
                return $this->html($mailable, $data);
            }
            
            // If second parameter is array, treat as view with data
            if (is_array($data)) {
                $html = $this->view->make($mailable, $data)->render();
                return $this->html($html, $callback ?: function ($message) {
                    //
                });
            }
            
            // Just a plain HTML string
            return $this->html($mailable, function ($message) {
                //
            });
        }

        throw new \InvalidArgumentException('Invalid mailable type');
    }

    protected function sendMailable(Mailable $mailable)
    {
        $mailable->send($this);

        return $this;
    }

    public function to($address, $name = null)
    {
        $this->to[] = compact('address', 'name');
        return $this;
    }

    public function cc($address, $name = null)
    {
        $this->cc[] = compact('address', 'name');
        return $this;
    }

    public function bcc($address, $name = null)
    {
        $this->bcc[] = compact('address', 'name');
        return $this;
    }

    public function subject($subject)
    {
        $this->subject = $subject;
        return $this;
    }

    public function from($address, $name = null)
    {
        $this->from = compact('address', 'name');
        return $this;
    }

    public function replyTo($address, $name = null)
    {
        $this->replyTo = compact('address', 'name');
        return $this;
    }

    public function attach($file, array $options = [])
    {
        $this->attachments[] = [
            'file' => $file,
            'options' => $options
        ];
        return $this;
    }

    public function attachData($data, $name, array $options = [])
    {
        $this->rawAttachments[] = [
            'data' => $data,
            'name' => $name,
            'options' => $options
        ];
        return $this;
    }

    public function raw($text, $callback)
    {
        $message = $this->createMessage();
        $message->setBody($text, 'text/plain');
        
        $callback($message);
        
        return $this->sendMessage($message);
    }

    public function html($html, $callback)
    {
        $message = $this->createMessage();
        $message->setBody($html, 'text/html');
        
        $callback($message);
        
        return $this->sendMessage($message);
    }

    protected function createMessage()
    {
        $transport = $this->transportManager->driver();
        $message = $transport->createMessage();

        if ($this->from) {
            $message->setFrom($this->from['address'], $this->from['name']);
        }

        foreach ($this->to as $recipient) {
            $message->addTo($recipient['address'], $recipient['name'] ?? null);
        }

        foreach ($this->cc as $recipient) {
            $message->addCc($recipient['address'], $recipient['name'] ?? null);
        }

        foreach ($this->bcc as $recipient) {
            $message->addBcc($recipient['address'], $recipient['name'] ?? null);
        }

        if ($this->subject) {
            $message->setSubject($this->subject);
        }

        if ($this->replyTo) {
            $message->setReplyTo($this->replyTo['address'], $this->replyTo['name']);
        }

        foreach ($this->attachments as $attachment) {
            $message->attach($attachment['file'], $attachment['options']);
        }

        foreach ($this->rawAttachments as $attachment) {
            $message->attachData($attachment['data'], $attachment['name'], $attachment['options']);
        }

        return $message;
    }

    protected function sendMessage($message)
    {
        if ($this->events) {
            $this->events->dispatch(new MessageSending($message));
        }

        $result = $this->transportManager->driver()->send($message);

        if ($this->events) {
            $this->events->dispatch(new MessageSent($message));
        }

        $this->reset();

        return $result;
    }

    protected function reset()
    {
        $this->to = [];
        $this->cc = [];
        $this->bcc = [];
        $this->subject = null;
        $this->replyTo = null;
        $this->attachments = [];
        $this->rawAttachments = [];
    }

    public function setFrom($address, $name = null)
    {
        $this->from = compact('address', 'name');
    }
}
