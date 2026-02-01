<?php

namespace Arpon\Database\Migrations;

use Arpon\Database\Connection;
use Exception;

use Arpon\Contracts\Database\Migrations\MigrationRepository;

class DatabaseMigrationRepository implements MigrationRepository
{
    /**
     * The database connection instance.
     *
     * @var \Arpon\Database\Connection
     */
    protected $connection;

    /**
     * The migrations table name.
     *
     * @var string
     */
    protected $table;

    /**
     * Create a new migration repository instance.
     *
     * @param  \Arpon\Database\Connection  $connection
     * @param  string  $table
     * @return void
     */
    public function __construct(Connection $connection, $table)
    {
        $this->connection = $connection;
        $this->table = $table;
    }

    /**
     * Get the completed migrations.
     *
     * @return array
     */
    public function getRan()
    {
        return $this->table()
            ->orderBy('batch', 'asc')
            ->orderBy('migration', 'asc')
            ->pluck('migration');
    }

    /**
     * Get the list of migrations.
     *
     * @param  int  $take
     * @return array
     */
    public function getMigrations($take)
    {
        return $this->table()
            ->orderBy('batch', 'desc')
            ->orderBy('migration', 'desc')
            ->take($take)
            ->get()
            ->all();
    }

    /**
     * Get the last migration batch.
     *
     * @return array
     */
    public function getLastBatch()
    {
        return $this->table()
            ->where('batch', $this->getLastBatchNumber())
            ->orderBy('migration', 'desc')
            ->get()
            ->all();
    }

    /**
     * Get the completed migrations with their run times.
     *
     * @return array
     */
    public function getCompletedMigrations()
    {
        return $this->table()
            ->orderBy('batch', 'asc')
            ->orderBy('migration', 'asc')
            ->pluck('migration')
            ->all();
    }

    /**
     * Log that a migration was run.
     *
     * @param  string  $file
     * @param  int  $runTime
     * @return void
     */
    public function log($file, $runTime)
    {
        $batch = $this->getNextBatchNumber();

        $this->table()->insert([
            'migration' => $file,
            'batch' => $batch,
            'run_time' => $runTime,
        ]);
    }

    /**
     * Delete a migration from the log.
     *
     * @param  string  $file
     * @return void
     */
    public function delete($file)
    {
        $this->table()->where('migration', $file)->delete();
    }

    /**
     * Create the migration repository data store.
     *
     * @return void
     */
    public function createRepository()
    {
        $schema = $this->connection->getSchemaBuilder();

        $schema->create($this->table, function ($table) {
            $table->string('migration');
            $table->integer('batch');
            $table->decimal('run_time', 10, 2);
        });
    }

    /**
     * Determine if the migration repository exists.
     *
     * @return bool
     */
    public function repositoryExists()
    {
        $schema = $this->connection->getSchemaBuilder();

        return $schema->hasTable($this->table);
    }

    /**
     * Delete the migration repository data store.
     *
     * @return void
     */
    public function deleteRepository()
    {
        $schema = $this->connection->getSchemaBuilder();

        $schema->drop($this->table);
    }

    /**
     * Get the next migration batch number.
     *
     * @return int
     */
    protected function getNextBatchNumber()
    {
        return $this->getLastBatchNumber() + 1;
    }

    /**
     * Get the last migration batch number.
     *
     * @return int
     */
    protected function getLastBatchNumber()
    {
        return $this->table()->max('batch') ?? 0;
    }

    /**
     * Get a query builder for the migration table.
     *
     * @return \Arpon\Database\Query\Builder
     */
    protected function table()
    {
        return $this->connection->table($this->table);
    }
}
