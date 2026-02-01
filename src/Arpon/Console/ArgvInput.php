<?php

namespace Arpon\Console;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputDefinition;

class ArgvInput implements InputInterface
{
    /**
     * The input arguments.
     *
     * @var array
     */
    protected $arguments = [];

    /**
     * The input options.
     *
     * @var array
     */
    protected $options = [];

    /**
     * Create a new ArgvInput instance.
     *
     * @return void
     */
    public function __construct()
    {
        global $argv;
        
        // Remove the script name
        $args = array_slice($argv, 1);
        
        $this->parseArguments($args);
    }

    /**
     * Parse the command line arguments.
     *
     * @param  array  $args
     * @return void
     */
    protected function parseArguments(array $args)
    {
        foreach ($args as $arg) {
            if (str_starts_with($arg, '--')) {
                $this->parseOption($arg);
            } else {
                $this->arguments[] = $arg;
            }
        }
    }

    /**
     * Parse an option.
     *
     * @param  string  $option
     * @return void
     */
    protected function parseOption($option)
    {
        $option = substr($option, 2);
        
        if (str_contains($option, '=')) {
            [$key, $value] = explode('=', $option, 2);
            $this->options[$key] = $value;
        } else {
            $this->options[$option] = true;
        }
    }

    /**
     * Get the first argument from the raw input.
     *
     * @return string|null
     */
    public function getFirstArgument(): ?string
    {
        return $this->arguments[0] ?? null;
    }

    /**
     * Returns true if the raw parameters contain a value.
     *
     * @param  string|number  $values
     * @return bool
     */
    public function hasParameterOption(string|array $values, bool $onlyParams = false): bool
    {
        $values = (array) $values;

        foreach ($this->arguments as $argument) {
            if (in_array($argument, $values, true)) {
                return true;
            }
        }

        foreach ($this->options as $option => $value) {
            if (in_array($option, $values, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns the value of a raw option after the options have been parsed.
     *
     * @param  string|number|array  $values
     * @param  string|bool|null  $default
     * @return string|bool|null
     */
    public function getParameterOption(string|array $values, string|bool|int|float|array|null $default = false, bool $onlyParams = false)
    {
        $values = (array) $values;

        foreach ($this->arguments as $argument) {
            if (in_array($argument, $values, true)) {
                return $argument;
            }
        }

        foreach ($this->options as $option => $value) {
            if (in_array($option, $values, true)) {
                return $value;
            }
        }

        return $default;
    }

    /**
     * Binds the current Input instance with the given arguments and options.
     *
     * @param  \Symfony\Component\Console\Input\InputDefinition  $definition
     * @return void
     */
    public function bind(InputDefinition $definition)
    {
        // Not implemented for ArgvInput
    }

    /**
     * Validates if arguments and options are correct.
     *
     * @return void
     */
    public function validate()
    {
        // Not implemented for ArgvInput
    }

    /**
     * Returns all argument values.
     *
     * @return array
     */
    public function getArguments(): array
    {
        return $this->arguments;
    }

    /**
     * Returns the value of a given argument.
     *
     * @param  string  $name
     * @return string|int|float|bool|null
     */
    public function getArgument(string $name)
    {
        return $this->arguments[$name] ?? null;
    }

    /**
     * Sets an argument value by name.
     *
     * @param  string  $name
     * @param  string|int|float|bool|null  $value
     * @return void
     */
    public function setArgument(string $name, mixed $value)
    {
        $this->arguments[$name] = $value;
    }

    /**
     * Returns true if an argument exists by name.
     *
     * @param  string  $name
     * @return bool
     */
    public function hasArgument(string $name): bool
    {
        return array_key_exists($name, $this->arguments);
    }

    /**
     * Returns all option values.
     *
     * @return array
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * Returns the value of a given option.
     *
     * @param  string  $name
     * @return string|int|float|bool|null
     */
    public function getOption(string $name)
    {
        return $this->options[$name] ?? null;
    }

    /**
     * Sets an option value by name.
     *
     * @param  string  $name
     * @param  string|int|float|bool|null  $value
     * @return void
     */
    public function setOption(string $name, mixed $value)
    {
        $this->options[$name] = $value;
    }

    /**
     * Returns true if an option exists by name.
     *
     * @param  string  $name
     * @return bool
     */
    public function hasOption(string $name): bool
    {
        return array_key_exists($name, $this->options);
    }

    /**
     * Is this input means interactive?
     *
     * @return bool
     */
    public function isInteractive(): bool
    {
        return false;
    }

    /**
     * Sets the input to be interactive.
     *
     * @param  bool  $interactive
     * @return void
     */
    public function setInteractive(bool $interactive)
    {
        // Not implemented for ArgvInput
    }
}
