<?php

namespace Arpon\Mail;

use Arpon\Contracts\Mail\Mailable as MailableContract;

class Mailable implements MailableContract
{
    protected $to = [];
    protected $cc = [];
    protected $bcc = [];
    protected $from;
    protected $replyTo;
    protected $subject;
    protected $view;
    protected $textView;
    protected $viewData = [];
    protected $attachments = [];
    protected $rawAttachments = [];
    protected $html;
    protected $raw;

    public function send($mailer)
    {
        $this->build();

        if ($this->html) {
            return $mailer->html($this->html, function ($message) {
                $this->buildMessage($message);
            });
        }

        if ($this->raw) {
            return $mailer->raw($this->raw, function ($message) {
                $this->buildMessage($message);
            });
        }

        if ($this->view) {
            $html = $this->renderView($this->view, $this->viewData);
            $text = $this->textView ? $this->renderView($this->textView, $this->viewData) : null;

            return $mailer->html($html, function ($message) use ($text) {
                $this->buildMessage($message);
                
                if ($text) {
                    $message->addPart($text, 'text/plain');
                }
            });
        }

        throw new \Exception('No content defined for mailable');
    }

    protected function buildMessage($message)
    {
        foreach ($this->to as $recipient) {
            $message->addTo($recipient['address'], $recipient['name'] ?? null);
        }

        foreach ($this->cc as $recipient) {
            $message->addCc($recipient['address'], $recipient['name'] ?? null);
        }

        foreach ($this->bcc as $recipient) {
            $message->addBcc($recipient['address'], $recipient['name'] ?? null);
        }

        if ($this->from) {
            $message->setFrom($this->from['address'], $this->from['name']);
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
    }

    protected function renderView($view, $data)
    {
        return app('view')->make($view, $data)->render();
    }

    protected function build()
    {
        //
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

    public function from($address, $name = null)
    {
        $this->from = compact('address', 'name');
        return $this;
    }

    public function subject($subject)
    {
        $this->subject = $subject;
        return $this;
    }

    public function getSubject()
    {
        return $this->subject;
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

    public function with($key, $value = null)
    {
        if (is_array($key)) {
            $this->viewData = array_merge($this->viewData, $key);
        } else {
            $this->viewData[$key] = $value;
        }
        return $this;
    }

    public function view($view, $data = [])
    {
        $this->view = $view;
        $this->with($data);
        return $this;
    }

    public function text($view, $data = [])
    {
        $this->textView = $view;
        $this->with($data);
        return $this;
    }

    public function html($html)
    {
        $this->html = $html;
        return $this;
    }

    public function raw($text)
    {
        $this->raw = $text;
        return $this;
    }
}
