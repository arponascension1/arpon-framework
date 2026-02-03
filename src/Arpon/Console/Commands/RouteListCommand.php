<?php

namespace Arpon\Console\Commands;

use Arpon\Console\Command;
use Closure;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RouteListCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'route:list';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List all registered routes';

    /**
     * Execute the console command.
     *
     * @param  \Symfony\Component\Console\Input\InputInterface  $input
     * @param  \Symfony\Component\Console\Output\OutputInterface  $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // Set facade application if not already set
        \Arpon\Support\Facades\Facade::setFacadeApplication($this->application);

        $router = $this->application->make('router');

        // Load routes using the centralized method
        $reflection = new \ReflectionClass($this->application);
        $method = $reflection->getMethod('loadRoutes');
        $method->setAccessible(true);
        $method->invoke($this->application);

        $routes = $router->getRoutes()->getRoutes();

        if (empty($routes)) {
            $output->writeln('<info>Your application doesn\'t have any routes.</info>');
            return 0;
        }

        $table = new Table($output);
        $table->setHeaders(['Method', 'URI', 'Name', 'Action']);

        foreach ($routes as $route) {
            $table->addRow([
                implode('|', $route->getMethods()),
                $route->getUri(),
                $route->getName() ?: '',
                $this->formatAction($route->getAction())
            ]);
        }

        $table->render();

        return 0;
    }

    /**
     * Format the route action for display.
     *
     * @param  mixed  $action
     * @return string
     */
    protected function formatAction($action)
    {
        if ($action instanceof Closure) {
            return 'Closure';
        }

        if (is_array($action)) {
            if (isset($action['uses'])) {
                if ($action['uses'] instanceof Closure) {
                    return 'Closure';
                }
                if (is_array($action['uses'])) {
                    return implode('@', $action['uses']);
                }
                return is_string($action['uses']) ? $action['uses'] : 'Closure';
            }
            
            // Handle array with controller and method
            if (count($action) === 2 && isset($action[0]) && isset($action[1])) {
                return $action[0] . '@' . $action[1];
            }
            
            return implode('@', array_filter($action, 'is_string'));
        }

        if (is_string($action)) {
            return $action;
        }

        return 'Unknown';
    }
}
