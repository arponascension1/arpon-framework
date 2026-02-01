<?php

namespace Arpon\Console\Commands;

use Arpon\Console\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MakeModelCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:model {name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new Eloquent model class';

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
        
        // Handle nested models
        $parts = explode('/', str_replace('\\', '/', $name));
        $className = array_pop($parts);
        $namespaceSuffix = !empty($parts) ? '\\' . implode('\\', $parts) : '';
        $pathSuffix = !empty($parts) ? implode('/', $parts) . '/' : '';

        $namespace = 'App\\Models' . $namespaceSuffix;
        $path = "app/Models/{$pathSuffix}{$className}.php";
        $filePath = $this->application->basePath($path);

        // Create directory if it doesn't exist
        $directory = dirname($filePath);
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        // Check if file already exists
        if (file_exists($filePath)) {
            $output->writeln("<error>Model already exists:</error> {$filePath}");
            return 1;
        }

        // Generate the Model class content
        $content = $this->generateModelContent($className, $namespace);

        // Write the file
        if (file_put_contents($filePath, $content) === false) {
            $output->writeln('<error>Failed to create Model file.</error>');
            return 1;
        }

        $output->writeln("<info>Model created successfully:</info> {$filePath}");

        return 0;
    }

    /**
     * Generate the Model class content.
     *
     * @param  string  $className
     * @param  string  $namespace
     * @return string
     */
    protected function generateModelContent($className, $namespace)
    {
        return <<<PHP
<?php

namespace {$namespace};

use Arpon\Database\Eloquent\Model;

class {$className} extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected array \$fillable = [
        //
    ];
}

PHP;
    }
}
