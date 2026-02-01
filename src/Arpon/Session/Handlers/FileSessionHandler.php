<?php

namespace Arpon\Session\Handlers;

use SessionHandlerInterface;

class FileSessionHandler implements SessionHandlerInterface
{
    protected $path;
    protected $lifetime;

    public function __construct($path, $lifetime)
    {
        $this->path = $path;
        $this->lifetime = $lifetime;

        if (!is_dir($this->path)) {
            mkdir($this->path, 0775, true);
        }
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
        $sessionFile = $this->path . '/' . $sessionId;

        if (file_exists($sessionFile) && is_file($sessionFile)) {
            $content = file_get_contents($sessionFile);

            if ($this->isExpired($sessionFile)) {
                $this->destroy($sessionId);
                return '';
            }

            return $content;
        }

        return '';
    }

    public function write($sessionId, $data): bool
    {
        $sessionFile = $this->path . '/' . $sessionId;

        return file_put_contents($sessionFile, $data, LOCK_EX) !== false;
    }

    public function destroy($sessionId): bool
    {
        $sessionFile = $this->path . '/' . $sessionId;

        if (file_exists($sessionFile)) {
            return unlink($sessionFile);
        }

        return true;
    }

    public function gc($maxlifetime): int|false
    {
        $files = glob($this->path . '/*');

        if ($files === false) {
            return true;
        }

        $deleted = 0;
        foreach ($files as $file) {
            if (is_file($file) && $this->isExpired($file)) {
                if (unlink($file)) {
                    $deleted++;
                }
            }
        }

        return $deleted;
    }

    protected function isExpired($sessionFile)
    {
        if (!file_exists($sessionFile)) {
            return true;
        }

        return (filemtime($sessionFile) + $this->lifetime * 60) < time();
    }
}
