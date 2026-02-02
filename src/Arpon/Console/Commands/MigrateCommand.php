<?php

namespace Arpon\Console\Commands;

use Arpon\Console\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class MigrateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'migrate {--force : Force the operation to run when in production} {--pretend : Dump the SQL queries that would be run}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run the database migrations';

    /**
     * Execute the console command.
     *
     * @param  \Symfony\Component\Console\Input\InputInterface  $input
     * @param  \Symfony\Component\Console\Output\OutputInterface  $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (!$this->confirmToProceed('Application is in production. Do you want to continue?', $input, $output)) {
            $output->writeln('<comment>Command cancelled.</comment>');
            return 1;
        }

        $this->prepareDatabase($input, $output);

        $migrator = $this->application->make('migrator');
        
        $migrator->run([$this->application->databasePath('migrations')]);

        foreach ($migrator->getNotes() as $note) {
            $output->writeln($note);
        }

        if (count($migrator->getNotes()) === 0) {
            $output->writeln('<info>Nothing to migrate.</info>');
        } else {
            $output->writeln('<info>Migration completed successfully.</info>');
        }

        return 0;
    }

    /**
     * Confirm before proceeding with the action.
     *
     * @param  string  $warning
     * @param  \Symfony\Component\Console\Input\InputInterface  $input
     * @param  \Symfony\Component\Console\Output\OutputInterface  $output
     * @return bool
     */
    protected function confirmToProceed($warning, $input, $output)
    {
        if ($this->application['config']->get('app.env', 'production') === 'production') {
            if ($input->hasOption('force') && $input->getOption('force')) {
                return true;
            }

            $helper = $this->getHelper('question');
            $question = new ConfirmationQuestion(
                "<question>{$warning} (yes/no)</question> ",
                false
            );

            return $helper->ask($input, $output, $question);
        }

        return true;
    }

    /**
     * Prepare the migration database for running.
     *
     * @param  \Symfony\Component\Console\Input\InputInterface  $input
     * @param  \Symfony\Component\Console\Output\OutputInterface  $output
     * @return void
     */
    protected function prepareDatabase($input, $output)
    {
        $this->ensureDatabaseExists($input, $output);
        
        $repository = $this->application->make('migration.repository');
        
        if (!$repository->repositoryExists()) {
            $output->writeln('<comment>Migration table not found.</comment>');
            $this->callArtisan('migrate:install', $output);
        }
    }

    /**
     * Call another console command.
     *
     * @param  string  $command
     * @param  \Symfony\Component\Console\Output\OutputInterface  $output
     * @return int
     */
    protected function callArtisan($command, $output)
    {
        $kernel = $this->application->make(\Arpon\Console\Kernel::class);
        return $kernel->call($command, [], $output);
    }

    /**
     * Ensure the database exists.
     *
     * @param  \Symfony\Component\Console\Input\InputInterface  $input
     * @param  \Symfony\Component\Console\Output\OutputInterface  $output
     * @return void
     */
    protected function ensureDatabaseExists($input, $output)
    {
        $config = $this->application['config'];
        $connectionName = $config->get('database.default', 'mysql');
        $connectionConfig = $config->get("database.connections.{$connectionName}", []);
        $driver = $connectionConfig['driver'] ?? 'mysql';
        
        // Skip for SQLite in-memory databases
        if ($driver === 'sqlite' && ($connectionConfig['database'] ?? '') === ':memory:') {
            return;
        }
        
        try {
            // Try to connect to the database first
            $this->application['db']->connection()->getPdo();
        } catch (\Exception $e) {
            if ($this->shouldCreateDatabase($e, $driver)) {
                $this->createDatabase($input, $output, $connectionName, $connectionConfig);
            } else {
                throw $e;
            }
        }
    }

    /**
     * Determine if the exception indicates a missing database.
     *
     * @param  \Exception  $e
     * @param  string  $driver
     * @return bool
     */
    protected function shouldCreateDatabase(\Exception $e, $driver)
    {
        $message = $e->getMessage();
        
        if ($driver === 'mysql') {
            return strpos($message, 'Unknown database') !== false;
        } elseif ($driver === 'sqlite') {
            return strpos($message, 'unable to open') !== false 
                || strpos($message, 'no such file') !== false;
        }
        
        return false;
    }

    /**
     * Create the database.
     *
     * @param  \Symfony\Component\Console\Input\InputInterface  $input
     * @param  \Symfony\Component\Console\Output\OutputInterface  $output
     * @param  string  $connectionName
     * @param  array  $config
     * @return void
     */
    protected function createDatabase($input, $output, $connectionName, $config)
    {
        $database = $config['database'] ?? 'database';
        $driver = $config['driver'] ?? 'mysql';
        
        $helper = $this->getHelper('question');
        $question = new ConfirmationQuestion(
            "<question>Database '{$database}' does not exist on connection '{$connectionName}'. Create it? (yes/no)</question> ",
            false
        );
        
        if (!$helper->ask($input, $output, $question)) {
            $output->writeln('<error>Database creation cancelled.</error>');
            throw new \RuntimeException("Database '{$database}' does not exist.");
        }
        
        $output->writeln("<info>Creating database '{$database}'...</info>");
        
        try {
            if ($driver === 'sqlite') {
                $this->createSQLiteDatabase($database);
            } elseif ($driver === 'mysql') {
                $this->createMySQLDatabase($database, $config);
            }
            
            $output->writeln("<info>Database '{$database}' created successfully.</info>");
        } catch (\Exception $e) {
            $output->writeln("<error>Failed to create database: {$e->getMessage()}</error>");
            throw $e;
        }
    }
    
    /**
     * Create a SQLite database file.
     *
     * @param  string  $path
     * @return void
     */
    protected function createSQLiteDatabase($path)
    {
        if ($path === ':memory:') {
            return;
        }
        
        $directory = dirname($path);
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }
        
        if (!file_exists($path)) {
            touch($path);
        }
    }
    
    /**
     * Create a MySQL database.
     *
     * @param  string  $name
     * @param  array  $config
     * @return void
     */
    protected function createMySQLDatabase($name, $config)
    {
        $pdo = new \PDO(
            $this->getMySQLDsn($config, false),
            $config['username'] ?? 'root',
            $config['password'] ?? '',
            [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]
        );
        
        $charset = $config['charset'] ?? 'utf8mb4';
        $collation = $config['collation'] ?? 'utf8mb4_unicode_ci';
        
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$name}` CHARACTER SET {$charset} COLLATE {$collation}");
    }
    
    /**
     * Get MySQL DSN without database name.
     *
     * @param  array  $config
     * @param  bool  $includeDatabase
     * @return string
     */
    protected function getMySQLDsn(array $config, bool $includeDatabase = true)
    {
        $dsn = "mysql:host={$config['host']}";
        
        if (isset($config['port'])) {
            $dsn .= ";port={$config['port']}";
        }
        
        if ($includeDatabase && isset($config['database'])) {
            $dsn .= ";dbname={$config['database']}";
        }
        
        if (isset($config['unix_socket'])) {
            $dsn .= ";unix_socket={$config['unix_socket']}";
        }
        
        return $dsn;
    }
}

