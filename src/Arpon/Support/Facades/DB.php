<?php

namespace Arpon\Support\Facades;

/**
 * @method static \Arpon\Database\Query\Builder table(string $table)
 * @method static \Arpon\Database\Query\Builder select(string $query, array $bindings = [])
 * @method static bool insert(string $query, array $bindings = [])
 * @method static int update(string $query, array $bindings = [])
 * @method static int delete(string $query, array $bindings = [])
 * @method static bool statement(string $query, array $bindings = [])
 * @method static mixed transaction(\Closure $callback, int $attempts = 1)
 * @method static void beginTransaction()
 * @method static void commit()
 * @method static void rollBack()
 * @method static int transactionLevel()
 * @method static array pretend(\Closure $callback)
 * @method static \PDO getPdo()
 * @method static string getDatabaseName()
 *
 * @see \Arpon\Database\DatabaseManager
 * @see \Arpon\Database\Connection
 */
class DB extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'db';
    }
}
