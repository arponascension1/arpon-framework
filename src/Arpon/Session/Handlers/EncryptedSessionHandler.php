<?php

namespace Arpon\Session\Handlers;

use SessionHandlerInterface;
use Arpon\Encryption\Encrypter;

class EncryptedSessionHandler implements SessionHandlerInterface
{
    /**
     * The underlying session handler.
     *
     * @var \SessionHandlerInterface
     */
    protected $handler;

    /**
     * The encrypter instance.
     *
     * @var \Arpon\Encryption\Encrypter
     */
    protected $encrypter;

    /**
     * Create a new encrypted session handler instance.
     *
     * @param  \SessionHandlerInterface  $handler
     * @param  \Arpon\Encryption\Encrypter  $encrypter
     * @return void
     */
    public function __construct(SessionHandlerInterface $handler, Encrypter $encrypter)
    {
        $this->handler = $handler;
        $this->encrypter = $encrypter;
    }

    /**
     * {@inheritdoc}
     */
    public function open($savePath, $sessionName): bool
    {
        return $this->handler->open($savePath, $sessionName);
    }

    /**
     * {@inheritdoc}
     */
    public function close(): bool
    {
        return $this->handler->close();
    }

    /**
     * {@inheritdoc}
     */
    public function read($sessionId): string|false
    {
        $data = $this->handler->read($sessionId);

        if ($data === false || $data === '') {
            return $data;
        }

        try {
            return $this->encrypter->decrypt($data, false);
        } catch (\Exception $e) {
            return '';
        }
    }

    /**
     * {@inheritdoc}
     */
    public function write($sessionId, $data): bool
    {
        return $this->handler->write($sessionId, $this->encrypter->encrypt($data, false));
    }

    /**
     * {@inheritdoc}
     */
    public function destroy($sessionId): bool
    {
        return $this->handler->destroy($sessionId);
    }

    /**
     * {@inheritdoc}
     */
    public function gc($maxlifetime): int|false
    {
        return $this->handler->gc($maxlifetime);
    }

    /**
     * Get the underlying handler.
     *
     * @return \SessionHandlerInterface
     */
    public function getHandler()
    {
        return $this->handler;
    }
}
