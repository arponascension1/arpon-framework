<?php

namespace Arpon\Console\Commands;

use Arpon\Console\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;

class MakeControllerCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:controller {name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new controller class';

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
        
        // Handle nested controllers (e.g., Admin/UserController)
        $parts = explode('/', str_replace('\\', '/', $name));
        $className = array_pop($parts);
        $namespaceSuffix = !empty($parts) ? '\\' . implode('\\', $parts) : '';
        $pathSuffix = !empty($parts) ? implode('/', $parts) . '/' : '';

        // Ensure the name ends with 'Controller'
        if (!str_ends_with($className, 'Controller')) {
            $className .= 'Controller';
        }

        $namespace = 'App\\Http\\Controllers' . $namespaceSuffix;
        $path = "app/Http/Controllers/{$pathSuffix}{$className}.php";
        $filePath = $this->application->basePath($path);

        // Create directory if it doesn't exist
        $directory = dirname($filePath);
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        // Check if file already exists
        if (file_exists($filePath)) {
            $output->writeln("<error>Controller already exists:</error> {$filePath}");
            return 1;
        }

        // Generate the Controller class content
        $content = $this->generateControllerContent($className, $namespace);

        // Write the file
        if (file_put_contents($filePath, $content) === false) {
            $output->writeln('<error>Failed to create Controller file.</error>');
            return 1;
        }

        $output->writeln("<info>Controller created successfully:</info> {$filePath}");

        return 0;
    }

    /**
     * Generate the Controller class content.
     *
     * @param  string  $className
     * @param  string  $namespace
     * @return string
     */
    protected function generateControllerContent($className, $namespace)
    {
        return <<<PHP
<?php

namespace {$namespace};

use Arpon\Http\Controller;
use Arpon\Http\Request;

class {$className} extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Arpon\Http\Response
     */
    public function index()
    {
        //
    }
}

PHP;
    }
}
