<?php

namespace Arpon\Contracts\Database\Migrations;

interface MigrationRepository
{
    /**
     * Get the ran migrations.
     *
     * @return array
     */
    public function getRan();

    /**
     * Log that a migration was run.
     *
     * @param  string  $file
     * @param  int  $batch
     * @return void
     */
    public function log($file, $runTime);

    /**
     * Delete a migration from the log.
     *
     * @param  string  $file
     * @return void
     */
    public function delete($file);

    /**
     * Get the last migration batch.
     *
     * @return array
     */
    public function getLastBatch();

    /**
     * Get the completed migrations with their run times.
     *
     * @return array
     */
    public function getCompletedMigrations();

    /**
     * Create the migration repository data store.
     *
     * @return void
     */
    public function createRepository();

    /**
     * Determine if the migration repository exists.
     *
     * @return bool
     */
    public function repositoryExists();

    /**
     * Delete the migration repository data store.
     *
     * @return void
     */
    public function deleteRepository();
}
