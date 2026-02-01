<?php

namespace Arpon\Mail\Transport;

use Arpon\Contracts\Log\Log;
use Arpon\Contracts\Mail\Transport;
use Arpon\Mail\Message;

class LogTransport implements Transport
{
    /**
     * The Logger instance.
     *
     * @var \Arpon\Contracts\Log\Log
     */
    protected $logger;

    /**
     * The log channel to use.
     *
     * @var string|null
     */
    protected $channel;

    /**
     * Create a new log transport instance.
     *
     * @param  \Arpon\Contracts\Log\Log  $logger
     * @param  string|null  $channel
     * @return void
     */
    public function __construct(Log $logger, $channel = null)
    {
        $this->logger = $logger;
        $this->channel = $channel;
    }

    /**
     * Send the given message.
     *
     * @param  \Arpon\Mail\Message  $message
     * @return int
     */
    public function send($message)
    {
        $logger = $this->logger;

        if ($this->channel && method_exists($logger, 'channel')) {
            $logger = $logger->channel($this->channel);
        }

        $logger->debug($this->getMimeEntityString($message));

        return count($message->getTo());
    }

    /**
     * Get a string representation of the message.
     *
     * @param  \Arpon\Mail\Message  $message
     * @return string
     */
    protected function getMimeEntityString($message)
    {
        $to = $this->formatAddresses($message->getTo());
        $subject = $message->getSubject();
        $body = $message->getBody();

        return "To: {$to}\nSubject: {$subject}\n\n{$body}";
    }
    
    /**
     * Format the given addresses.
     *
     * @param  array  $addresses
     * @return string
     */
    protected function formatAddresses(array $addresses)
    {
        return implode(', ', array_map(function ($name, $email) {
            return $name ? "{$name} <{$email}>" : $email;
        }, $addresses, array_keys($addresses)));
    }

    /**
     * Create a new message instance.
     *
     * @return \Arpon\Mail\Message
     */
    public function createMessage()
    {
        return new Message();
    }
}
