<?php

namespace Arpon\Mail;

class Message
{
    protected $from = [];
    protected $to = [];
    protected $cc = [];
    protected $bcc = [];
    protected $replyTo = [];
    protected $subject;
    protected $body;
    protected $contentType = 'text/html';
    protected $attachments = [];
    protected $parts = [];

    public function from($address, $name = null)
    {
        return $this->setFrom($address, $name);
    }

    public function setFrom($address, $name = null)
    {
        $this->from = [$address => $name];
        return $this;
    }

    public function to($address, $name = null)
    {
        return $this->addTo($address, $name);
    }

    public function addTo($address, $name = null)
    {
        $this->to[$address] = $name;
        return $this;
    }

    public function cc($address, $name = null)
    {
        return $this->addCc($address, $name);
    }

    public function addCc($address, $name = null)
    {
        $this->cc[$address] = $name;
        return $this;
    }

    public function bcc($address, $name = null)
    {
        return $this->addBcc($address, $name);
    }

    public function addBcc($address, $name = null)
    {
        $this->bcc[$address] = $name;
        return $this;
    }

    public function replyTo($address, $name = null)
    {
        return $this->setReplyTo($address, $name);
    }

    public function setReplyTo($address, $name = null)
    {
        $this->replyTo = [$address => $name];
        return $this;
    }

    public function subject($subject)
    {
        return $this->setSubject($subject);
    }

    public function setSubject($subject)
    {
        $this->subject = $subject;
        return $this;
    }

    public function setBody($body, $contentType = 'text/html')
    {
        $this->body = $body;
        $this->contentType = $contentType;
        return $this;
    }

    public function addPart($content, $contentType = 'text/plain')
    {
        $this->parts[] = [
            'content' => $content,
            'content_type' => $contentType
        ];
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
        $this->attachments[] = [
            'data' => $data,
            'name' => $name,
            'options' => $options
        ];
        return $this;
    }

    public function getFrom()
    {
        return $this->from;
    }

    public function getTo()
    {
        return $this->to;
    }

    public function getCc()
    {
        return $this->cc;
    }

    public function getBcc()
    {
        return $this->bcc;
    }

    public function getReplyTo()
    {
        return $this->replyTo;
    }

    public function getSubject()
    {
        return $this->subject;
    }

    public function getBody()
    {
        return $this->body;
    }

    public function getContentType()
    {
        return $this->contentType;
    }

    public function getAttachments()
    {
        return $this->attachments;
    }

    public function getParts()
    {
        return $this->parts;
    }
}
