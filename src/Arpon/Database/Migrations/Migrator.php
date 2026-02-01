<?php

namespace Arpon\Database\Migrations;

use Arpon\Contracts\Database\ConnectionResolver as Resolver;
use Arpon\Contracts\Database\Migrations\MigrationRepository;
use Arpon\Database\Schema\Builder;
use Exception;
use Throwable;

class Migrator
{
    /**
     * The database connection resolver instance.
     *
     * @var \Arpon\Contracts\Database\ConnectionResolver
     */
    protected $resolver;

    /**
     * The notes for the current operation.
     *
     * @var array
     */
    protected $notes = [];

    /**
     * The migration repository instance.
     *
     * @var \Arpon\Contracts\Database\Migrations\MigrationRepository
     */
    protected $repository;

    /**
     * Create a new migrator instance.
     *
     * @param  \Arpon\Contracts\Database\ConnectionResolver  $resolver
     * @param  \Arpon\Contracts\Database\Migrations\MigrationRepository  $repository
     * @return void
     */
    public function __construct(Resolver $resolver, MigrationRepository $repository)
    {
        $this->resolver = $resolver;
        $this->repository = $repository;
    }

    /**
     * Run the pending migrations.
     *
     * @param  array  $paths
     * @param  array  $options
     * @return array
     */
    public function run($paths = [], array $options = [])
    {
        $this->notes = [];

        $files = $this->getMigrationFiles($paths);

        $ran = $this->repository->getRan();

        $pending = $this->pendingMigrations($files, $ran);

        $this->requireFiles($pending);

        foreach ($pending as $file) {
            $this->runUp($file);
        }

        return $pending;
    }

    /**
     * Rollback the last migration operation.
     *
     * @param  array  $paths
     * @param  array  $options
     * @return array
     */
    public function rollback($paths = [], array $options = [])
    {
        $this->notes = [];

        $files = $this->getMigrationFiles($paths);

        $ran = $this->repository->getRan();

        if (empty($ran)) {
            $this->note('<info>No migrations to rollback.</info>');
            return [];
        }

        $rolledBack = [];

        // Rollback in reverse order to handle foreign key constraints
        foreach (array_reverse($files) as $file) {
            if (in_array($file, $ran)) {
                $this->requireFiles([$file]);
                $this->runDown($file);
                $rolledBack[] = $file;
            }
        }

        return $rolledBack;
    }

    /**
     * Reset all migrations.
     *
     * @param  array  $paths
     * @param  bool  $pretend
     * @return array
     */
    public function reset($paths = [], $pretend = false)
    {
        $this->notes = [];

        $files = $this->getMigrationFiles($paths);

        $ran = $this->repository->getRan();

        $rolledBack = [];

        // Disable foreign key constraints to allow dropping tables
        $this->disableForeignKeyConstraints();

        try {
            // Rollback in reverse order to handle foreign key constraints
            foreach (array_reverse($files) as $file) {
                if (in_array($file, $ran)) {
                    $this->requireFiles([$file]);
                    $this->runDown($file);
                    $rolledBack[] = $file;
                }
            }
        } finally {
            // Re-enable foreign key constraints
            $this->enableForeignKeyConstraints();
        }

        return $rolledBack;
    }

    /**
     * Disable foreign key constraints.
     *
     * @return void
     */
    protected function disableForeignKeyConstraints()
    {
        $schema = $this->resolver->connection()->getSchemaBuilder();
        $schema->disableForeignKeyConstraints();
    }

    /**
     * Enable foreign key constraints.
     *
     * @return void
     */
    protected function enableForeignKeyConstraints()
    {
        $schema = $this->resolver->connection()->getSchemaBuilder();
        $schema->enableForeignKeyConstraints();
    }

    /**
     * Run "up" a migration instance.
     *
     * @param  string  $file
     * @return void
     */
    protected function runUp($file)
    {
        $migration = $this->resolve($file);

        $this->notes[] = '<info>Migrating:</info> ' . $file;

        $startTime = microtime(true);

        $migration->up();

        $runTime = round(microtime(true) - $startTime, 2);

        $this->repository->log($file, $runTime);

        $this->notes[] = '<info>Migrated:</info>  ' . $file . ' (' . $runTime . 's)';
    }

    /**
     * Run "down" a migration instance.
     *
     * @param  string  $file
     * @return void
     */
    protected function runDown($file)
    {
        $migration = $this->resolve($file);

        $this->notes[] = '<info>Rolling back:</info> ' . $file;

        $migration->down();

        $this->repository->delete($file);

        $this->notes[] = '<info>Rolled back:</info>  ' . $file;
    }

    /**
     * Resolve a migration instance from a file.
     *
     * @param  string  $file
     * @return \Arpon\Database\Migrations\Migration
     */
    public function resolve($file)
    {
        // Include the migration file and return the migration instance
        $migration = require $file;
        
        if ($migration instanceof Migration) {
            return $migration;
        }
        
        throw new \Exception("Migration file must return a Migration instance: {$file}");
    }

    /**
     * Get the migration class name from a migration file.
     *
     * @param  string  $file
     * @return string
     */
    protected function getMigrationClass($file)
    {
        // Extract the class name from the file content
        $content = file_get_contents($file);
        
        // Look for "return new class extends Migration"
        if (preg_match('/return new class extends Migration/', $content)) {
            // For anonymous classes, we need to return the migration instance directly
            return null;
        }
        
        // Look for "class ClassName extends Migration"
        if (preg_match('/class\s+(\w+)\s+extends\s+Migration/', $content, $matches)) {
            return $matches[1];
        }
        
        // Fallback to filename-based class name
        return basename($file, '.php');
    }

    /**
     * Get all of the migration files in a given path.
     *
     * @param  array  $paths
     * @return array
     */
    public function getMigrationFiles($paths)
    {
        $files = [];

        foreach ($paths as $path) {
            $files = array_merge($files, glob($path . '/*.php'));
        }

        return array_filter($files, function ($file) {
            return !in_array(basename($file), ['.gitkeep', '.DS_Store']);
        });
    }

    /**
     * Get the pending migrations for the given paths.
     *
     * @param  array  $files
     * @param  array  $ran
     * @return array
     */
    protected function pendingMigrations($files, $ran)
    {
        return array_diff($files, $ran);
    }

    /**
     * Require in all the migration files in a given path.
     *
     * @param  array  $files
     * @return void
     */
    public function requireFiles(array $files)
    {
        // Ensure the Migration base class is loaded
        if (!class_exists(Migration::class)) {
            require_once __DIR__ . '/Migration.php';
        }
        
        foreach ($files as $file) {
            require_once $file;
        }
    }

    /**
     * Get all of the notes for the operations.
     *
     * @return array
     */
    public function getNotes()
    {
        return $this->notes;
    }

    /**
     * Add a note to the notes collection.
     *
     * @param  string  $note
     * @return void
     */
    protected function note($note)
    {
        $this->notes[] = $note;
    }
}
