<?php

namespace Arpon\Exceptions;

use Arpon\Http\Response;

class GeneralExceptionHandler
{
    protected $app;
    protected $config;

    public function __construct($app, $config)
    {
        $this->app = $app;
        $this->config = $config;
    }

    /**
     * Handle general exceptions and throwables.
     * 
     * @param \Throwable $e
     * @param mixed $request
     * @return \Arpon\Http\Response
     */
    public function handle($e, $request)
    {
        // Check for custom renderable handlers first
        $renderables = $this->config->getRenderables() ?? [];
        
        foreach ($renderables as $renderable) {
            $result = $renderable($e, $request);
            if ($result !== null) {
                return $result;
            }
        }

        $statusCode = 500;
        $headers = [];

        if ($e instanceof \Arpon\Database\Eloquent\ModelNotFoundException) {
            $statusCode = 404;
        } elseif ($e instanceof \Arpon\Http\Exceptions\HttpException) {
            $statusCode = $e->getStatusCode();
            $headers = $e->getHeaders();
        }
        
        // Default handling for JSON requests
        if (method_exists($request, 'wantsJson') && $request->wantsJson()) {
            return (new Response())->json([
                'message' => $statusCode === 500 ? 'Internal Server Error' : $e->getMessage(),
                'error' => $e->getMessage(),
            ], $statusCode);
        }

        // Default handling for web requests - use view
        return $this->renderView($e, $statusCode, $headers);
    }

    /**
     * Render the error view.
     * 
     * @param \Throwable $e
     * @param int $statusCode
     * @param array $headers
     * @return \Arpon\Http\Response
     */
    protected function renderView($e, $statusCode = 500, array $headers = [])
    {
        // Path to custom error views
        $viewPath = __DIR__ . '/views/' . $statusCode . '.php';
        
        if (!file_exists($viewPath)) {
            // Fallback to generic error view if specific one doesn't exist
            $viewPath = __DIR__ . '/views/error.php';
        }

        if (!file_exists($viewPath)) {
            // Fallback to 500 view if generic error view doesn't exist
            $viewPath = __DIR__ . '/views/500.php';
        }
        
        if (!file_exists($viewPath)) {
            // Fallback to simple HTML if view doesn't exist
            return new Response(
                "<h1>{$statusCode} Error</h1><p>" . htmlspecialchars($e->getMessage() ?: 'Something went wrong.') . "</p>",
                $statusCode,
                array_merge(['Content-Type' => 'text/html; charset=UTF-8'], $headers)
            );
        }

        ob_start();
        $displayMessage = $e->getMessage();
        if ($statusCode === 404 && ($e instanceof \Arpon\Database\Eloquent\ModelNotFoundException || empty($displayMessage))) {
            $displayMessage = 'The page you are looking for could not be found.';
        }
        
        extract([
            'exception' => $e,
            'message' => $displayMessage,
            'code' => $statusCode,
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString(),
        ]);
        require $viewPath;
        $html = ob_get_clean();

        return new Response($html, $statusCode, array_merge(['Content-Type' => 'text/html; charset=UTF-8'], $headers));
    }
}
