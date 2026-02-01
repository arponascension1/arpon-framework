<?php

namespace Arpon\Console\Commands;

use Arpon\Console\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;

class MakeRequestCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:request {name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new form request class';

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

        // Ensure the name ends with 'Request'
        if (!str_ends_with($name, 'Request')) {
            $name .= 'Request';
        }

        $className = $name;
        $filePath = $this->application->basePath("app/Http/Requests/{$className}.php");

        // Create directory if it doesn't exist
        $directory = dirname($filePath);
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        // Check if file already exists
        if (file_exists($filePath)) {
            $output->writeln("<error>FormRequest already exists:</error> {$filePath}");
            return 1;
        }

        // Generate the FormRequest class content
        $content = $this->generateRequestContent($className);

        // Write the file
        if (file_put_contents($filePath, $content) === false) {
            $output->writeln('<error>Failed to create FormRequest file.</error>');
            return 1;
        }

        $output->writeln("<info>FormRequest created successfully:</info> {$filePath}");

        return 0;
    }

    /**
     * Generate the FormRequest class content.
     *
     * @param  string  $className
     * @return string
     */
    protected function generateRequestContent($className)
    {
        return <<<PHP
<?php

namespace App\Http\Requests;

use Arpon\Foundation\Http\FormRequest;

class {$className} extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            //
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            //
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array
     */
    public function attributes(): array
    {
        return [
            //
        ];
    }
}

PHP;
    }
}
