<?php

namespace Arpon\Database\Migrations;

use Arpon\Database\Schema\Blueprint;
use Arpon\Database\Schema\Builder;
use Arpon\Contracts\Database\ConnectionResolver as Resolver;

abstract class Migration
{
    /**
     * The database connection instance.
     *
     * @var \Arpon\Database\Connection
     */
    protected $connection;

    /**
     * The schema builder instance.
     *
     * @var \Arpon\Database\Schema\Builder
     */
    protected $schema;

    /**
     * The name of the migration.
     *
     * @var string
     */
    protected $name;

    /**
     * Create a new migration instance.
     *
     * @param  string  $name
     * @return void
     */
    public function __construct($name = null)
    {
        $this->name = $name;
    }

    /**
     * Get the migration connection instance.
     *
     * @return \Arpon\Database\Connection
     */
    public function getConnection()
    {
        if ($this->connection) {
            return $this->connection;
        }

        return app('db')->connection();
    }

    /**
     * Set the database connection instance.
     *
     * @param  \Arpon\Database\Connection  $connection
     * @return $this
     */
    public function setConnection($connection)
    {
        $this->connection = $connection;

        return $this;
    }

    /**
     * Get the schema builder instance.
     *
     * @return \Arpon\Database\Schema\Builder
     */
    public function getSchema()
    {
        if ($this->schema) {
            return $this->schema;
        }

        return $this->schema = $this->getConnection()->getSchemaBuilder();
    }

    /**
     * Run the migrations.
     *
     * @return void
     */
    abstract public function up();

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    abstract public function down();

    /**
     * Create a new table on the schema.
     *
     * @param  string  $table
     * @param  \Closure  $callback
     * @return void
     */
    protected function create($table, $callback)
    {
        $this->getSchema()->create($table, $callback);
    }

    /**
     * Modify a table on the schema.
     *
     * @param  string  $table
     * @param  \Closure  $callback
     * @return void
     */
    protected function table($table, $callback)
    {
        $this->getSchema()->table($table, $callback);
    }

    /**
     * Drop a table from the schema.
     *
     * @param  string  $table
     * @return void
     */
    protected function drop($table)
    {
        $this->getSchema()->drop($table);
    }

    /**
     * Drop a table from the schema if it exists.
     *
     * @param  string  $table
     * @return void
     */
    protected function dropIfExists($table)
    {
        $this->getSchema()->dropIfExists($table);
    }

    /**
     * Rename a table on the schema.
     *
     * @param  string  $from
     * @param  string  $to
     * @return void
     */
    protected function rename($from, $to)
    {
        $this->getSchema()->rename($from, $to);
    }

    /**
     * Enable foreign key constraints.
     *
     * @return void
     */
    protected function enableForeignKeyConstraints()
    {
        $this->getSchema()->enableForeignKeyConstraints();
    }

    /**
     * Disable foreign key constraints.
     *
     * @return void
     */
    protected function disableForeignKeyConstraints()
    {
        $this->getSchema()->disableForeignKeyConstraints();
    }
}
