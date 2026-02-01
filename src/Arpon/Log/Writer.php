<?php

namespace Arpon\Log;

use Arpon\Contracts\Log\Log as LogContract;

class Writer implements LogContract
{
    protected $app;
    protected $loggers = [];
    protected $channel;

    public function __construct($app)
    {
        $this->app = $app;
    }

    public function channel($channel)
    {
        $this->channel = $channel;
        return $this;
    }

    public function emergency($message, array $context = [])
    {
        $this->log('emergency', $message, $context);
    }

    public function alert($message, array $context = [])
    {
        $this->log('alert', $message, $context);
    }

    public function critical($message, array $context = [])
    {
        $this->log('critical', $message, $context);
    }

    public function error($message, array $context = [])
    {
        $this->log('error', $message, $context);
    }

    public function warning($message, array $context = [])
    {
        $this->log('warning', $message, $context);
    }

    public function notice($message, array $context = [])
    {
        $this->log('notice', $message, $context);
    }

    public function info($message, array $context = [])
    {
        $this->log('info', $message, $context);
    }

    public function debug($message, array $context = [])
    {
        $this->log('debug', $message, $context);
    }

    public function log($level, $message, array $context = [])
    {
        $config = $this->app['config']->get('logging');
        $channel = $this->channel ?: ($config['default'] ?? 'single');
        $channelConfig = $config['channels'][$channel] ?? $config['channels']['single'] ?? [];
        
        $timestamp = date('Y-m-d H:i:s');
        $contextStr = !empty($context) ? ' ' . json_encode($context) : '';
        $logMessage = "[{$timestamp}] {$level}: {$message}{$contextStr}" . PHP_EOL;

        $logFile = $channelConfig['path'] ?? $this->app->storagePath('logs/arpon.log');
        $logDir = dirname($logFile);

        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }

        file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);

        $this->channel = null;
    }

    public function write($level, $message, array $context = [])
    {
        $this->log($level, $message, $context);
    }
}