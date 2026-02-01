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
    protected $signature = 'make:migration {name}';

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
        
        // Ensure the name ends with 'Migration'
        if (!str_ends_with($name, 'Migration')) {
            $name .= 'Migration';
        }

        $className = $name;
        $tableName = $this->getTableName($name);
        $isAlterMigration = $this->isAlterMigration($name);
        $timestamp = date('Y_m_d_His');
        $fileName = "{$timestamp}_{$className}.php";
        $filePath = $this->application->databasePath("migrations/{$fileName}");

        // Create directory if it doesn't exist
        $directory = dirname($filePath);
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        // Generate the migration class content
        $content = $this->generateMigrationContent($tableName, $isAlterMigration);

        // Write the file
        if (file_put_contents($filePath, $content) === false) {
            $output->writeln('<error>Failed to create migration file.</error>');
            return 1;
        }

        $output->writeln("<info>Migration created successfully:</info> {$filePath}");

        return 0;
    }

    /**
     * Check if this is an alter table migration (add/drop columns).
     *
     * @param  string  $name
     * @return bool
     */
    protected function isAlterMigration($name)
    {
        $patterns = ['add_', 'drop_', 'modify_', 'change_', 'remove_', 'update_'];
        
        foreach ($patterns as $pattern) {
            if (str_starts_with(strtolower($name), $pattern)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Get the table name from the migration name.
     *
     * @param  string  $name
     * @return string
     */
    protected function getTableName($name)
    {
        // Convert CamelCase to snake_case and remove 'Migration' suffix
        $tableName = preg_replace('/([A-Z])/', '_$1', $name);
        $tableName = str_replace('_Migration', '', $tableName);
        $tableName = strtolower($tableName);
        
        // Remove leading underscore if exists
        if (str_starts_with($tableName, '_')) {
            $tableName = substr($tableName, 1);
        }
        
        // Remove 'create_' and '_table' prefixes/suffixes
        if (str_starts_with($tableName, 'create_')) {
            $tableName = substr($tableName, 7);
        }
        if (str_ends_with($tableName, '_table')) {
            $tableName = substr($tableName, 0, -6);
        }
        
        // For alter migrations, extract table name (e.g., 'add_phone_to_users' -> 'users')
        if (preg_match('/(add|drop|modify|change|remove|update)_(.+)_to_(.+)/', $tableName, $matches)) {
            $tableName = $matches[3];
        } elseif (preg_match('/(add|drop|modify|change|remove|update)_(.+)_from_(.+)/', $tableName, $matches)) {
            $tableName = $matches[3];
        } elseif (preg_match('/(add|drop|modify|change|remove|update)_(.+)_in_(.+)/', $tableName, $matches)) {
            $tableName = $matches[3];
        }
        
        return $tableName;
    }

    /**
     * Generate the migration content.
     *
     * @param  string  $tableName
     * @param  bool  $isAlterMigration
     * @return string
     */
    protected function generateMigrationContent($tableName, $isAlterMigration)
    {
        $content = "<?php\n\n";
        $content .= "use Arpon\\Database\\Migrations\\Migration;\n";
        $content .= "use Arpon\\Database\\Schema\\Blueprint;\n\n";
        $content .= "return new class extends Migration\n";
        $content .= "{\n";
        $content .= "    /**\n";
        $content .= "     * Run the migrations.\n";
        $content .= "     *\n";
        $content .= "     * @return void\n";
        $content .= "     */\n";
        $content .= "    public function up()\n";
        $content .= "    {\n";
        
        if ($isAlterMigration) {
            // Alter table migration
            $content .= "        \$this->table('{$tableName}', function (Blueprint \$table) {\n";
            $content .= "            // Add your columns here\n";
            $content .= "            // \$table->string('column_name')->nullable();\n";
            $content .= "        });\n";
        } else {
            // Create table migration
            $content .= "        \$this->create('{$tableName}', function (Blueprint \$table) {\n";
            $content .= "            \$table->id();\n";
            $content .= "            \$table->timestamps();\n";
            $content .= "        });\n";
        }
        
        $content .= "    }\n\n";
        $content .= "    /**\n";
        $content .= "     * Reverse the migrations.\n";
        $content .= "     *\n";
        $content .= "     * @return void\n";
        $content .= "     */\n";
        $content .= "    public function down()\n";
        $content .= "    {\n";
        
        if ($isAlterMigration) {
            // Alter table rollback
            $content .= "        \$this->table('{$tableName}', function (Blueprint \$table) {\n";
            $content .= "            // Drop your columns here\n";
            $content .= "            // \$table->dropColumn('column_name');\n";
            $content .= "        });\n";
        } else {
            // Drop table
            $content .= "        \$this->dropIfExists('{$tableName}');\n";
        }
        
        $content .= "    }\n";
        $content .= "};\n";
        
        return $content;
    }
}
