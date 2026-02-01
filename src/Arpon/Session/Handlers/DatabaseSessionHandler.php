<?php

namespace Arpon\Session\Handlers;

use SessionHandlerInterface;
use Arpon\Database\DatabaseManager;

class DatabaseSessionHandler implements SessionHandlerInterface
{
    protected $connection;
    protected $table;
    protected $lifetime;

    public function __construct(DatabaseManager $connection, $table, $lifetime)
    {
        $this->connection = $connection;
        $this->table = $table;
        $this->lifetime = $lifetime;
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
        $session = $this->connection->table($this->table)
            ->where('id', $sessionId)
            ->first();

        if ($session) {
            $lastActivity = is_array($session) ? $session['last_activity'] : $session->last_activity;
            $payload = is_array($session) ? $session['payload'] : $session->payload;

            if ($this->isExpired($lastActivity)) {
                $this->destroy($sessionId);
                return '';
            }

            return $payload;
        }

        return '';
    }

    public function write($sessionId, $data): bool
    {
        $payload = $data;

        $this->connection->table($this->table)->updateOrInsert(
            ['id' => $sessionId],
            [
                'payload' => $payload,
                'last_activity' => time(),
                'user_id' => null,
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
            ]
        );

        return true;
    }

    public function destroy($sessionId): bool
    {
        $this->connection->table($this->table)
            ->where('id', $sessionId)
            ->delete();

        return true;
    }

    public function gc($maxlifetime): int|false
    {
        $this->connection->table($this->table)
            ->where('last_activity', '<', time() - $this->lifetime * 60)
            ->delete();

        return true;
    }

    protected function isExpired($lastActivity)
    {
        return ($lastActivity + $this->lifetime * 60) < time();
    }
}
