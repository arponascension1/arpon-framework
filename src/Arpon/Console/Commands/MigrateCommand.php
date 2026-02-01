<?php

namespace Arpon\Console\Commands;

use Arpon\Console\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Arpon\Database\Schema\Blueprint;

class MigrateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'migrate {--force : Force the operation to run when in production}';

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
        $repository = $this->application->make('migration.repository');
        
        // Create database if it doesn't exist (for MySQL)
        $this->ensureDatabaseExists($input, $output);
        
        // Automatically create migration table if it doesn't exist
        if (!$repository->repositoryExists()) {
            $output->writeln('<info>Migration table not found. Creating it...</info>');
            $repository->createRepository();
            $output->writeln('<info>Migration table created successfully.</info>');
        }

        // Ensure session table exists if database driver is used
        $this->ensureSessionTableExists($output);
        
        $migrator = $this->application->make('migrator');
        
        $migrator->run([$this->application->databasePath('migrations')]);

        foreach ($migrator->getNotes() as $note) {
            $output->writeln($note);
        }

        $output->writeln('<info>all migration is complete</info>');

        return 0;
    }

    /**
     * Ensure the session table exists if using database driver.
     *
     * @param  \Symfony\Component\Console\Output\OutputInterface  $output
     * @return void
     */
    protected function ensureSessionTableExists(OutputInterface $output)
    {
        $config = $this->application['config'];
        
        if ($config->get('session.driver') !== 'database') {
            return;
        }
        
        $table = $config->get('session.table', 'sessions');
        $migrationsPath = $this->application->databasePath('migrations');
        
        // Check if migration file already exists
        $migrationExists = false;
        if (is_dir($migrationsPath)) {
            $files = scandir($migrationsPath);
            foreach ($files as $file) {
                if (strpos($file, "create_{$table}_table") !== false) {
                    $migrationExists = true;
                    break;
                }
            }
        }

        if (!$migrationExists) {
            $db = $this->application['db'];
            $schema = $db->connection()->getSchemaBuilder();
            
            if (!$schema->hasTable($table)) {
                $output->writeln("<info>Session migration file not found. Creating it...</info>");
                
                $timestamp = date('Y_m_d_His');
                $fileName = "{$timestamp}_create_{$table}_table.php";
                $filePath = $migrationsPath . DIRECTORY_SEPARATOR . $fileName;

                $content = $this->generateSessionMigrationContent($table);
                
                if (!is_dir($migrationsPath)) {
                    mkdir($migrationsPath, 0755, true);
                }
                
                file_put_contents($filePath, $content);
                $output->writeln("<info>Session migration file created successfully: {$fileName}</info>");
            }
        }
    }

    /**
     * Generate the session migration content.
     *
     * @param  string  $table
     * @return string
     */
    protected function generateSessionMigrationContent($table)
    {
        return <<<PHP
<?php

use Arpon\Database\Migrations\Migration;
use Arpon\Database\Schema\Blueprint;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        \$this->create('{$table}', function (Blueprint \$table) {
            \$table->string('id')->primary();
            \$table->text('payload');
            \$table->integer('last_activity')->unsigned();
            \$table->integer('user_id')->nullable()->unsigned();
            \$table->string('ip_address', 45)->nullable();
            \$table->text('user_agent')->nullable();
            \$table->index(['last_activity'], 'sessions_last_activity_index');
            \$table->index(['user_id'], 'sessions_user_id_index');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        \$this->dropIfExists('{$table}');
    }
};
PHP;
    }

    /**
     * Ensure the database exists.
     *
     * @param  \Symfony\Component\Console\Input\InputInterface  $input
     * @param  \Symfony\Component\Console\Output\OutputInterface  $output
     * @return void
     */
    protected function ensureDatabaseExists(InputInterface $input, OutputInterface $output)
    {
        $config = $this->application['config'];
        $connection = $config->get('database.default', 'mysql');
        $driver = $config->get("database.connections.{$connection}.driver", 'mysql');
        
        if ($driver === 'mysql') {
            $database = $config->get("database.connections.{$connection}.database");
            $host = $config->get("database.connections.{$connection}.host", '127.0.0.1');
            $port = $config->get("database.connections.{$connection}.port", '3306');
            $username = $config->get("database.connections.{$connection}.username", 'root');
            $password = $config->get("database.connections.{$connection}.password", '');
            
            if ($database) {
                try {
                    // Try to connect to the database first
                    $this->application['db']->connection()->getPdo();
                } catch (\Exception $e) {
                    if (strpos($e->getMessage(), 'Unknown database') !== false) {
                        // Database doesn't exist, ask for confirmation
                        $helper = $this->getHelper('question');
                        $question = new ConfirmationQuestion(
                            "<question>Database '{$database}' does not exist. Do you want to create it? (yes/no)</question> ",
                            false
                        );
                        
                        if (!$helper->ask($input, $output, $question)) {
                            $output->writeln('<error>Database creation cancelled. Please create the database manually.</error>');
                            throw new \Exception("Database '{$database}' does not exist and creation was cancelled.");
                        }
                        
                        $output->writeln("<info>Creating database '{$database}'...</info>");
                        
                        try {
                            // Connect without database name to create database
                            $pdo = new \PDO(
                                "mysql:host={$host};port={$port}",
                                $username,
                                $password,
                                [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]
                            );
                            
                            $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$database}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                            $output->writeln("<info>Database '{$database}' created successfully.</info>");
                        } catch (\Exception $createException) {
                            $output->writeln("<error>Failed to create database: {$createException->getMessage()}</error>");
                            $output->writeln("<error>Please create the database manually and try again.</error>");
                            throw $createException;
                        }
                    } else {
                        throw $e;
                    }
                }
            }
        }
    }
}
