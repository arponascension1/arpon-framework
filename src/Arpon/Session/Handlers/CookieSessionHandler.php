<?php

namespace Arpon\Session\Handlers;

use SessionHandlerInterface;

class CookieSessionHandler implements SessionHandlerInterface
{
    protected $config;

    public function __construct(array $config)
    {
        $this->config = array_merge([
            'name' => 'arpon_session',
            'path' => '/',
            'domain' => null,
            'secure' => false,
            'http_only' => true,
            'same_site' => 'lax',
        ], $config);
    }

    public function open($savePath, $sessionName): bool
    {
        return true;
    }

    public function close(): bool
    {
        return true;
    }

    public function read($sessionId): string|false
    {
        if (isset($_COOKIE[$this->config['name']])) {
            return base64_decode($_COOKIE[$this->config['name']]);
        }

        return '';
    }

    public function write($sessionId, $data): bool
    {
        $encoded = base64_encode($data);
        
        $cookieOptions = [
            'expires' => 0,
            'path' => $this->config['path'],
            'domain' => $this->config['domain'],
            'secure' => $this->config['secure'],
            'httponly' => $this->config['http_only'],
        ];

        if (version_compare(PHP_VERSION, '7.3.0', '>=')) {
            $cookieOptions['samesite'] = $this->config['same_site'];
        } else {
            // For PHP < 7.3, include SameSite in path
            $cookieOptions['path'] .= '; samesite=' . $this->config['same_site'];
        }

        return setcookie($this->config['name'], $encoded, $cookieOptions);
    }

    public function destroy($sessionId): bool
    {
        if (isset($_COOKIE[$this->config['name']])) {
            unset($_COOKIE[$this->config['name']]);
            
            $cookieOptions = [
                'expires' => time() - 3600,
                'path' => $this->config['path'],
                'domain' => $this->config['domain'],
                'secure' => $this->config['secure'],
                'httponly' => $this->config['http_only'],
            ];

            if (version_compare(PHP_VERSION, '7.3.0', '>=')) {
                $cookieOptions['samesite'] = $this->config['same_site'];
            } else {
                $cookieOptions['path'] .= '; samesite=' . $this->config['same_site'];
            }

            setcookie($this->config['name'], '', $cookieOptions);
        }

        return true;
    }

    public function gc($maxlifetime): int|false
    {
        return true;
    }
}
