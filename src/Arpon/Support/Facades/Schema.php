<?php

namespace Arpon\Support\Facades;

/**
 * @method static void create(string $table, \Closure $callback)
 * @method static void table(string $table, \Closure $callback)
 * @method static void drop(string $table)
 * @method static void dropIfExists(string $table)
 * @method static void rename(string $from, string $to)
 * @method static bool hasTable(string $table)
 * @method static bool hasColumn(string $table, string $column)
 * @method static array getColumnListing(string $table)
 *
 * @see \Arpon\Database\Schema\Builder
 */
class Schema extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'db.schema';
    }
}
