<?php

namespace Arpon\Console\Commands;

use Arpon\Console\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;

class MakeMigrationCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:migration {name} {--create= : The table to be created} {--table= : The table to migrate}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new migration file';

    /**
     * Execute the console command.
     *
     * @param  \Symfony\Component\Console\Input\InputInterface  $input
     * @param  \Symfony\Component\Console\Output\OutputInterface  $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $name = $input->getArgument('name');
        
        // Properly handle options - only set if user actually provided them
        $create = null;
        $table = null;
        
        if ($input->hasParameterOption(['--create'])) {
            $create = $input->getOption('create');
            if ($create === null || $create === '') {
                $create = ''; // Explicit empty means extract from name
            }
        }
        
        if ($input->hasParameterOption(['--table'])) {
            $table = $input->getOption('table');
            if ($table === null || $table === '') {
                $table = ''; // Explicit empty means extract from name
            }
        }
        
        $timestamp = date('Y_m_d_His');
        $fileName = "{$timestamp}_{$name}.php";
        $filePath = $this->application->databasePath("migrations/{$fileName}");

        // Create directory if it doesn't exist
        $directory = dirname($filePath);
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        // Detect migration type and generate content
        $content = $this->generateMigrationContent($name, $create, $table);

        // Write the file
        if (file_put_contents($filePath, $content) === false) {
            $output->writeln('<error>Failed to create migration file.</error>');
            return 1;
        }

        $output->writeln("<info>Migration created successfully:</info> {$filePath}");

        return 0;
    }

    /**
     * Generate the migration content based on the migration name.
     *
     * @param  string  $name
     * @param  string|null  $create
     * @param  string|null  $table
     * @return string
     */
    protected function generateMigrationContent($name, $create = null, $table = null)
    {
        $nameLower = strtolower($name);
        
        // If --create option is provided, use create stub
        if ($create !== null) {
            $tableName = $create !== '' ? $create : $this->extractTableName($name);
            return $this->getStubContent('migration.create.stub', ['table' => $tableName]);
        }
        
        // If --table option is provided, use update stub
        if ($table !== null) {
            $tableName = $table !== '' ? $table : $this->extractTableName($name);
            return $this->getStubContent('migration.update.stub', ['table' => $tableName]);
        }
        
        // Check for add pattern FIRST (before create pattern): add_xxx_to_yyy_table or add_xxx_to_yyy
        if (preg_match('/^add_(.+)_to_(.+?)(?:_table)?$/', $nameLower, $matches)) {
            $columnNames = $matches[1];
            $tableName = $matches[2];
            return $this->getStubContent('migration.add.stub', [
                'table' => $tableName,
                'column' => $this->formatColumnName($columnNames)
            ]);
        }
        
        // Check for remove/drop column pattern: remove_xxx_from_yyy_table or drop_xxx_from_yyy
        if (preg_match('/^(?:remove|drop)_(.+)_(?:from|in)_(.+?)(?:_table)?$/', $nameLower, $matches)) {
            $columnNames = $matches[1];
            $tableName = $matches[2];
            return $this->getStubContent('migration.remove.stub', [
                'table' => $tableName,
                'column' => $this->formatColumnName($columnNames)
            ]);
        }
        
        // Check for modify pattern: modify_xxx_in_yyy_table
        if (preg_match('/^modify_(.+)_(?:in|on)_(.+?)(?:_table)?$/', $nameLower, $matches)) {
            $columnNames = $matches[1];
            $tableName = $matches[2];
            return $this->getStubContent('migration.modify.stub', [
                'table' => $tableName,
                'column' => $this->formatColumnName($columnNames)
            ]);
        }
        
        // Check for alter pattern: alter_xxx_table or change_xxx_table
        if (preg_match('/^(?:alter|change)_(.+?)(?:_table)?$/', $nameLower, $matches)) {
            $tableName = $matches[1];
            return $this->getStubContent('migration.alter.stub', ['table' => $tableName]);
        }
        
        // Check for update pattern: update_xxx_in_yyy_table
        if (preg_match('/^update_(.+)_(?:in|on)_(.+?)(?:_table)?$/', $nameLower, $matches)) {
            $columnNames = $matches[1];
            $tableName = $matches[2];
            return $this->getStubContent('migration.modify.stub', [
                'table' => $tableName,
                'column' => $this->formatColumnName($columnNames)
            ]);
        }
        
        // Check for create pattern: create_xxx_table
        if (preg_match('/^create_(.+)_table$/', $nameLower, $matches)) {
            $tableName = $matches[1];
            return $this->getStubContent('migration.create.stub', ['table' => $tableName]);
        }
        
        // Check for drop pattern: drop_xxx_table
        if (preg_match('/^drop_(.+)_table$/', $nameLower, $matches)) {
            $tableName = $matches[1];
            return $this->getStubContent('migration.drop.stub', ['table' => $tableName]);
        }
        
        // Check for rename pattern: rename_xxx_to_yyy_table or rename_xxx_to_yyy
        if (preg_match('/^rename_(.+)_to_(.+?)(?:_table)?$/', $nameLower, $matches)) {
            $tableFrom = $matches[1];
            $tableTo = $matches[2];
            return $this->getStubContent('migration.rename.stub', [
                'tableFrom' => $tableFrom,
                'tableTo' => $tableTo
            ]);
        }
        
        // Default: plain migration with no assumptions
        return $this->getStubContent('migration.plain.stub', []);
    }
    
    /**
     * Extract table name from migration name.
     *
     * @param  string  $name
     * @return string
     */
    protected function extractTableName($name)
    {
        $nameLower = strtolower($name);
        
        // Try to extract from common patterns
        if (preg_match('/^create_(.+)_table$/', $nameLower, $matches)) {
            return $matches[1];
        }
        
        if (preg_match('/_to_(.+?)(?:_table)?$/', $nameLower, $matches)) {
            return $matches[1];
        }
        
        if (preg_match('/_from_(.+?)(?:_table)?$/', $nameLower, $matches)) {
            return $matches[1];
        }
        
        if (preg_match('/_in_(.+?)(?:_table)?$/', $nameLower, $matches)) {
            return $matches[1];
        }
        
        // Remove common prefixes/suffixes
        $tableName = preg_replace('/^(create|drop|add|remove|modify|alter|update|change)_/', '', $nameLower);
        $tableName = preg_replace('/_(table|migration)$/', '', $tableName);
        
        return $tableName;
    }
    
    /**
     * Format column name from migration name.
     * Converts snake_case to readable format for hints.
     *
     * @param  string  $columnNames
     * @return string
     */
    protected function formatColumnName($columnNames)
    {
        // Replace underscores between words with spaces for readability
        // But keep them as valid identifiers in hints
        // e.g., "email_verified_at" stays as "email_verified_at"
        return $columnNames;
    }
    
    /**
     * Get stub content with replacements.
     *
     * @param  string  $stubName
     * @param  array  $replacements
     * @return string
     */
    protected function getStubContent($stubName, $replacements = [])
    {
        $stub = file_get_contents(__DIR__ . '/../stubs/' . $stubName);
        
        foreach ($replacements as $key => $value) {
            $stub = str_replace('{{ ' . $key . ' }}', $value, $stub);
        }
        
        return $stub;
    }
}
