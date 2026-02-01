<?php

namespace Arpon\Mail\Transport;

use Arpon\Contracts\Mail\Transport;
use Arpon\Mail\Message;
use Exception;

class SmtpTransport implements Transport
{
    protected $host;
    protected $port;
    protected $encryption;
    protected $username;
    protected $password;
    protected $timeout;
    protected $socket;

    public function __construct($host = 'localhost', $port = 587, $encryption = null, $username = null, $password = null, $timeout = 30)
    {
        $this->host = $host;
        $this->port = $port;
        $this->encryption = $encryption;
        $this->username = $username;
        $this->password = $password;
        $this->timeout = $timeout;
    }

    public function send($message)
    {
        try {
            $this->connect();
            
            $from = $message->getFrom();
            if (empty($from)) {
                throw new Exception("No sender address specified");
            }
            
            $fromEmail = array_key_first($from);
            
            // Send MAIL FROM command
            $this->sendCommand("MAIL FROM:<$fromEmail>");
            
            // Get all recipients
            $recipients = array_merge(
                array_keys($message->getTo()),
                array_keys($message->getCc()),
                array_keys($message->getBcc())
            );
            
            if (empty($recipients)) {
                throw new Exception("No recipients specified");
            }
            
            // Send RCPT TO for each recipient
            foreach ($recipients as $recipient) {
                $this->sendCommand("RCPT TO:<$recipient>");
            }
            
            // Send DATA command
            $this->sendCommand("DATA", 354);
            
            // Build and send message
            $headers = $this->buildHeaders($message);
            $body = $message->getBody();
            
            // Normalize line endings in body to \r\n as required by SMTP
            $body = str_replace(["\r\n", "\r", "\n"], "\r\n", $body);
            
            $data = $headers . "\r\n\r\n" . $body . "\r\n.";
            $this->sendCommand($data);
            
            // Send QUIT
            $this->sendCommand("QUIT", 221);
            $this->close();
            
            return 1;
        } catch (Exception $e) {
            $this->close();
            throw $e;
        }
    }

    protected function connect()
    {
        $protocol = ($this->encryption === 'ssl') ? 'ssl://' : '';
        $this->socket = @stream_socket_client(
            $protocol . $this->host . ':' . $this->port,
            $errno,
            $errstr,
            $this->timeout
        );

        if (!$this->socket) {
            throw new Exception("Could not connect to SMTP host: $errstr ($errno)");
        }

        $this->getResponse(); // Banner

        $this->sendCommand("EHLO " . ($_SERVER['SERVER_NAME'] ?? 'localhost'));

        if ($this->encryption === 'tls') {
            $this->sendCommand("STARTTLS", 220);
            if (!stream_socket_enable_crypto($this->socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                throw new Exception("Failed to enable crypto");
            }
            $this->sendCommand("EHLO " . ($_SERVER['SERVER_NAME'] ?? 'localhost'));
        }

        if ($this->username && $this->password) {
            $this->sendCommand("AUTH LOGIN", 334);
            $this->sendCommand(base64_encode($this->username), 334);
            $this->sendCommand(base64_encode($this->password), 235);
        }
    }

    protected function sendCommand($command, $expectedCode = 250)
    {
        fwrite($this->socket, $command . "\r\n");
        $response = $this->getResponse();
        
        $code = (int) substr($response, 0, 3);
        if ($expectedCode && $code !== $expectedCode) {
            throw new Exception("SMTP Error: $response (Expected $expectedCode)");
        }
        
        return $response;
    }

    protected function getResponse()
    {
        $response = '';
        while ($line = fgets($this->socket, 515)) {
            $response .= $line;
            if (substr($line, 3, 1) === ' ') {
                break;
            }
        }
        return $response;
    }

    protected function close()
    {
        if ($this->socket) {
            fclose($this->socket);
            $this->socket = null;
        }
    }

    public function createMessage()
    {
        return new Message();
    }

    protected function buildHeaders($message)
    {
        $headers = [];
        
        $headers[] = 'MIME-Version: 1.0';
        $headers[] = 'Date: ' . date('r');
        $headers[] = 'Subject: ' . $message->getSubject();
        
        if ($from = $message->getFrom()) {
            $headers[] = 'From: ' . $this->formatAddresses($from);
        }
        
        if ($to = $message->getTo()) {
            $headers[] = 'To: ' . $this->formatAddresses($to);
        }
        
        if ($replyTo = $message->getReplyTo()) {
            $headers[] = 'Reply-To: ' . $this->formatAddresses($replyTo);
        }
        
        if ($cc = $message->getCc()) {
            $headers[] = 'Cc: ' . $this->formatAddresses($cc);
        }
        
        $contentType = $message->getContentType() ?: 'text/html';
        $headers[] = "Content-Type: {$contentType}; charset=UTF-8";
        $headers[] = 'Message-ID: <' . md5(uniqid()) . '@' . ($this->host) . '>';
        
        return implode("\r\n", $headers);
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