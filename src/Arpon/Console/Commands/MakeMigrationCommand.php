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
        $create = $input->hasOption('create') ? $input->getOption('create') : null;
        $table = $input->hasOption('table') ? $input->getOption('table') : null;
        
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
        // If --create option is provided, use create stub
        if ($create !== null && $create !== '') {
            // --create=tablename
            $tableName = $create;
            return $this->getStubContent('migration.create.stub', ['table' => $tableName]);
        } elseif ($create === '') {
            // --create without value, extract from name
            $tableName = $this->extractTableName($name);
            return $this->getStubContent('migration.create.stub', ['table' => $tableName]);
        }
        
        // If --table option is provided, use update stub
        if ($table !== null && $table !== '') {
            // --table=tablename
            $tableName = $table;
            return $this->getStubContent('migration.update.stub', ['table' => $tableName]);
        } elseif ($table === '') {
            // --table without value, extract from name
            $tableName = $this->extractTableName($name);
            return $this->getStubContent('migration.update.stub', ['table' => $tableName]);
        }
        
        $nameLower = strtolower($name);
        
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
        
        // Check for add pattern: add_xxx_to_yyy_table or add_xxx_to_yyy
        if (preg_match('/^add_(.+)_to_(.+?)(?:_table)?$/', $nameLower, $matches)) {
            $tableName = $matches[2];
            return $this->getStubContent('migration.update.stub', ['table' => $tableName]);
        }
        
        // Check for remove/drop column pattern: remove_xxx_from_yyy_table or drop_xxx_from_yyy
        if (preg_match('/^(?:remove|drop)_(.+)_from_(.+?)(?:_table)?$/', $nameLower, $matches)) {
            $tableName = $matches[2];
            return $this->getStubContent('migration.update.stub', ['table' => $tableName]);
        }
        
        // Check if it starts with alter/modify/update/change
        if (preg_match('/^(alter|modify|update|change)_(.+?)(?:_table)?$/', $nameLower, $matches)) {
            $tableName = $matches[2];
            // Try to extract table name if pattern is like: modify_xxx_in_yyy
            if (preg_match('/^(?:alter|modify|update|change)_(.+)_(?:in|on)_(.+)$/', $nameLower, $subMatches)) {
                $tableName = $subMatches[2];
            }
            return $this->getStubContent('migration.update.stub', ['table' => $tableName]);
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
