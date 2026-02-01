<?php

namespace Arpon\Session\Handlers;

use SessionHandlerInterface;

class ArraySessionHandler implements SessionHandlerInterface
{
    /**
     * The array of session data.
     *
     * @var array
     */
    protected $storage = [];

    /**
     * {@inheritdoc}
     */
    public function open($savePath, $sessionName): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function close(): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function read($sessionId): string|false
    {
        return $this->storage[$sessionId] ?? '';
    }

    /**
     * {@inheritdoc}
     */
    public function write($sessionId, $data): bool
    {
        $this->storage[$sessionId] = $data;

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function destroy($sessionId): bool
    {
        unset($this->storage[$sessionId]);

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function gc($maxlifetime): int|false
    {
        return 0;
    }
}
