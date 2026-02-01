<?php

namespace Arpon\Console\Commands;

use Arpon\Console\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;

class MakeCommandCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:command {name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new Artisan command';

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
        
        // Ensure the name ends with 'Command'
        if (!str_ends_with($name, 'Command')) {
            $name .= 'Command';
        }

        $className = $name;
        $namespace = 'App\\Console\\Commands';
        $filePath = $this->application->path("app/Console/Commands/{$className}.php");

        // Create directory if it doesn't exist
        $directory = dirname($filePath);
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        // Generate the command class content
        $content = "<?php\n\n";
        $content .= "namespace {$namespace};\n\n";
        $content .= "use Arpon\Console\Command;\n";
        $content .= "use Symfony\Component\Console\Input\InputInterface;\n";
        $content .= "use Symfony\Component\Console\Output\OutputInterface;\n\n";
        $content .= "class {$className} extends Command\n";
        $content .= "{\n";
        $content .= "    /**\n";
        $content .= "     * The name and signature of the console command.\n";
        $content .= "     *\n";
        $content .= "     * @var string\n";
        $content .= "     */\n";
        $content .= "    protected \$signature = 'command:name';\n\n";
        $content .= "    /**\n";
        $content .= "     * The console command description.\n";
        $content .= "     *\n";
        $content .= "     * @var string\n";
        $content .= "     */\n";
        $content .= "    protected \$description = 'Command description';\n\n";
        $content .= "    /**\n";
        $content .= "     * Execute the console command.\n";
        $content .= "     *\n";
        $content .= "     * @param  \Symfony\Component\Console\Input\InputInterface  \$input\n";
        $content .= "     * @param  \Symfony\Component\Console\Output\OutputInterface  \$output\n";
        $content .= "     * @return int\n";
        $content .= "     */\n";
        $content .= "    protected function execute(InputInterface \$input, OutputInterface \$output)\n";
        $content .= "    {\n";
        $content .= "        \$output->writeln('Hello from {$className}!');\n\n";
        $content .= "        return 0;\n";
        $content .= "    }\n";
        $content .= "}\n";

        // Write the file
        if (file_put_contents($filePath, $content) === false) {
            $output->writeln('<error>Failed to create command file.</error>');
            return 1;
        }

        $output->writeln("<info>Command created successfully:</info> {$filePath}");

        return 0;
    }
}
