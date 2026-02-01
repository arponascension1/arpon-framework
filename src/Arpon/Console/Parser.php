<?php

namespace Arpon\Console;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class Parser
{
    /**
     * Parse the given console command definition into an array.
     *
     * @param  string  $expression
     * @return array
     */
    public static function parse($expression)
    {
        $name = static::commandName($expression);

        $arguments = static::arguments($expression);

        $options = static::options($expression);

        return [$name, $arguments, $options];
    }

    /**
     * Extract the command name from the given expression.
     *
     * @param  string  $expression
     * @return string
     */
    protected static function commandName($expression)
    {
        if (! preg_match('/[^\s]+/', $expression, $matches)) {
            throw new \InvalidArgumentException('Unable to determine command name from signature.');
        }

        return $matches[0];
    }

    /**
     * Extract all of the arguments from the expression.
     *
     * @param  string  $expression
     * @return array
     */
    protected static function arguments($expression)
    {
        preg_match_all('/\{\s*(.*?)\s*\}/', $expression, $matches);

        $arguments = [];

        foreach ($matches[1] as $argument) {
            // Skip if it's an option (starts with --)
            if (str_starts_with($argument, '--')) {
                continue;
            }
            
            if (preg_match('/^(.*?)(?:\s*=\s*(.*))?$/', $argument, $matches)) {
                $arguments[] = static::argument($matches[1], isset($matches[2]) ? $matches[2] : null);
            }
        }

        return $arguments;
    }

    /**
     * Create an argument instance from the given argument.
     *
     * @param  string  $argument
     * @param  string|null  $default
     * @return \Symfony\Component\Console\Input\InputArgument
     */
    protected static function argument($argument, $default = null)
    {
        $description = '';

        if (strpos($argument, ':') !== false) {
            [$argument, $description] = explode(':', $argument, 2);
        }

        switch (substr($argument, 0, 1)) {
            case '?':
                return new InputArgument(substr($argument, 1), InputArgument::OPTIONAL, $description, $default);
            case '*':
                return new InputArgument(substr($argument, 1), InputArgument::IS_ARRAY, InputArgument::OPTIONAL, $description);
            default:
                return new InputArgument($argument, InputArgument::REQUIRED, $description);
        }
    }

    /**
     * Extract all of the options from the expression.
     *
     * @param  string  $expression
     * @return array
     */
    protected static function options($expression)
    {
        // Match options with their full definition including defaults and descriptions
        preg_match_all('/\-\-([^\s\}]+)(?:\s*\})?/', $expression, $matches);

        $options = [];

        foreach ($matches[1] as $option) {
            $options[] = static::option($option);
        }

        return $options;
    }

    /**
     * Create an option instance from the given option.
     *
     * @param  string  $option
     * @return \Symfony\Component\Console\Input\InputOption
     */
    protected static function option($option)
    {
        $description = '';

        if (strpos($option, ':') !== false) {
            [$option, $description] = explode(':', $option, 2);
        }

        $shortcut = null;

        if (preg_match('/^([^\s|]+)(?:\s*\|\s*([^\s|]+))?/', $option, $matches)) {
            $shortcut = $matches[2] ?? null;
            $option = $matches[1];
        }

        if (preg_match('/^([^\s=]+)(?:\s*=\s*(.*))?$/', $option, $matches)) {
            if (isset($matches[2])) {
                $default = $matches[2];

                if ($default === 'true' || $default === 'false') {
                    $default = $default === 'true';
                } elseif ($default === 'null') {
                    $default = null;
                }

                return new InputOption($matches[1], $shortcut, InputOption::VALUE_OPTIONAL, $description, $default);
            }

            return new InputOption($matches[1], $shortcut, InputOption::VALUE_NONE, $description);
        }

        return new InputOption($option, $shortcut, InputOption::VALUE_NONE, $description);
    }
}
