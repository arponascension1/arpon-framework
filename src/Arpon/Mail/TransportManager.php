<?php

namespace Arpon\Mail;

use Arpon\Mail\Transport\ArrayTransport;
use Arpon\Mail\Transport\LogTransport;
use Arpon\Mail\Transport\MailgunTransport;
use Arpon\Mail\Transport\SmtpTransport;

class TransportManager
{
    protected $app;
    protected $transports = [];

    public function __construct($app)
    {
        $this->app = $app;
    }

    public function driver($name = null)
    {
        $name = $name ?: $this->getDefaultDriver();

        if (!isset($this->transports[$name])) {
            $this->transports[$name] = $this->createDriver($name);
        }

        return $this->transports[$name];
    }

    protected function getDefaultDriver()
    {
        return $this->app['config']['mail']['default'] ?? 'smtp';
    }

    protected function createDriver($name)
    {
        $config = $this->app['config']['mail']['mailers'][$name] ?? [];

        switch ($name) {
            case 'smtp':
                return $this->createSmtpTransport($config);
            case 'mailgun':
                return $this->createMailgunTransport($config);
            case 'array':
                return new ArrayTransport();
            case 'log':
                return $this->createLogTransport($config);
            default:
                throw new \InvalidArgumentException("Unsupported mail driver [{$name}].");
        }
    }

    protected function createSmtpTransport(array $config)
    {
        return new SmtpTransport(
            $config['host'] ?? 'localhost',
            $config['port'] ?? 587,
            $config['encryption'] ?? null,
            $config['username'] ?? null,
            $config['password'] ?? null,
            $config['timeout'] ?? 30
        );
    }

    protected function createMailgunTransport(array $config)
    {
        if (!isset($config['secret'])) {
            throw new \InvalidArgumentException('Mailgun requires secret key.');
        }

        if (!isset($config['domain'])) {
            throw new \InvalidArgumentException('Mailgun requires domain.');
        }

        return new MailgunTransport(
            $config['secret'],
            $config['domain'],
            $config['endpoint'] ?? null
        );
    }

    protected function createLogTransport(array $config)
    {
        return new LogTransport($this->app['log'], $config['channel'] ?? null);
    }

    public function extend($name, callable $resolver)
    {
        $this->transports[$name] = $resolver;
    }
}
