<?php

namespace Arpon\Auth\Passwords;

use Arpon\Contracts\Database\Connection;
use Arpon\Contracts\Auth\TokenRepository;
use Arpon\Contracts\Auth\CanResetPassword;
use Arpon\Database\Query\Builder;
use Arpon\Support\Str;
use Arpon\Support\Carbon;

class DatabaseTokenRepository implements TokenRepository
{
    protected Connection $connection;
    protected string $table;
    protected string $hashKey;
    protected int $expires;

    public function __construct(Connection $connection, string $table, string $hashKey, int $expires = 60)
    {
        $this->connection = $connection;
        $this->table = $table;
        $this->hashKey = $hashKey;
        $this->expires = $expires;
    }

    public function create(object $user)
    {
        $this->deleteExisting($user);

        $email = $user->getEmailForPasswordReset();

        $token = $this->createNewToken();

        $this->getTable()->insert([
            'email' => $email,
            'token' => password_hash($token, PASSWORD_BCRYPT),
            'created_at' => new Carbon,
        ]);

        return $token;
    }

    public function exists(object $user, $token)
    {
        $record = (array) $this->getTable()->where(
            'email', $user->getEmailForPasswordReset()
        )->first();

        return $record &&
               ! $this->tokenExpired($record['created_at']) &&
                 password_verify($token, $record['token']);
    }

    public function recentlyCreatedToken(object $user)
    {
        $record = (array) $this->getTable()->where(
            'email', $user->getEmailForPasswordReset()
        )->first();

        return $record && ! $this->tokenExpired($record['created_at']);
    }

    protected function tokenExpired($createdAt)
    {
        return Carbon::parse($createdAt)->addMinutes($this->expires)->isPast();
    }

    public function delete(object $user)
    {
        $this->deleteExisting($user);
    }

    protected function deleteExisting(object $user)
    {
        $this->getTable()->where('email', $user->getEmailForPasswordReset())->delete();
    }

    public function deleteExpired()
    {
        $expiredAt = Carbon::now()->subMinutes($this->expires);

        $this->getTable()->where('created_at', '<', $expiredAt)->delete();
    }

    protected function createNewToken()
    {
        return hash_hmac('sha256', Str::random(40), $this->hashKey);
    }

    protected function getTable()
    {
        return $this->connection->table($this->table);
    }

    public function getConnection()
    {
        return $this->connection;
    }
}