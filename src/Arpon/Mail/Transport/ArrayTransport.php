<?php

namespace Arpon\Mail\Transport;

use Arpon\Contracts\Mail\Transport;
use Arpon\Mail\Message;

class ArrayTransport implements Transport
{
    protected $messages = [];

    public function send($message)
    {
        $this->messages[] = [
            'to' => $message->getTo(),
            'from' => $message->getFrom(),
            'subject' => $message->getSubject(),
            'body' => $message->getBody(),
            'cc' => $message->getCc(),
            'bcc' => $message->getBcc(),
            'replyTo' => $message->getReplyTo(),
            'attachments' => $message->getAttachments(),
        ];

        return 1;
    }

    public function createMessage()
    {
        return new Message();
    }

    public function getMessages()
    {
        return $this->messages;
    }

    public function flush()
    {
        $this->messages = [];
    }
}
