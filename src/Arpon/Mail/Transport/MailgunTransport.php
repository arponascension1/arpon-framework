<?php

namespace Arpon\Mail\Transport;

use Arpon\Contracts\Mail\Transport;
use Arpon\Mail\Message;

class MailgunTransport implements Transport
{
    protected $secret;
    protected $domain;
    protected $endpoint;

    public function __construct($secret, $domain, $endpoint = null)
    {
        $this->secret = $secret;
        $this->domain = $domain;
        $this->endpoint = $endpoint ?: 'https://api.mailgun.net/v3';
    }

    public function send($message)
    {
        $data = [
            'from' => $this->formatAddresses($message->getFrom()),
            'to' => $this->formatAddresses($message->getTo()),
            'subject' => $message->getSubject(),
            'html' => $message->getBody(),
        ];

        if ($cc = $message->getCc()) {
            $data['cc'] = $this->formatAddresses($cc);
        }

        if ($bcc = $message->getBcc()) {
            $data['bcc'] = $this->formatAddresses($bcc);
        }

        if ($replyTo = $message->getReplyTo()) {
            $data['h:Reply-To'] = $this->formatAddresses($replyTo);
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->endpoint . '/' . $this->domain . '/messages');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERPWD, 'api:' . $this->secret);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            throw new \Exception('Mailgun API error: ' . $response);
        }

        return 1;
    }

    public function createMessage()
    {
        return new Message();
    }

    protected function formatAddresses($addresses)
    {
        if (is_string($addresses)) {
            return $addresses;
        }
        
        $formatted = [];
        foreach ($addresses as $address => $name) {
            if (is_numeric($address)) {
                $formatted[] = $name;
            } else {
                $formatted[] = $name ? "{$name} <{$address}>" : $address;
            }
        }
        
        return implode(', ', $formatted);
    }
}
