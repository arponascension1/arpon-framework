<?php

namespace Arpon\Exceptions;

use Arpon\Http\Response;
use Arpon\Routing\Exceptions\RouteNotFoundException;

class RouteNotFoundExceptionHandler
{
    protected $app;
    protected $config;

    public function __construct($app, $config)
    {
        $this->app = $app;
        $this->config = $config;
    }

    /**
     * Handle route not found exception.
     * 
     * @param RouteNotFoundException $e
     * @param mixed $request
     * @return Response
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
        
        // Default handling for JSON requests
        if (method_exists($request, 'wantsJson') && $request->wantsJson()) {
            return (new Response())->json([
                'message' => 'Not Found',
                'path' => $e->getPath(),
                'method' => $e->getMethod(),
            ], 404);
        }

        // Default handling for web requests - use view
        return $this->renderView($e);
    }

    /**
     * Render the 404 view.
     * 
     * @param RouteNotFoundException $e
     * @return Response
     */
    protected function renderView(RouteNotFoundException $e): Response
    {
        // Use generic error page
        $viewPath = __DIR__ . '/views/error.php';
        
        if (!file_exists($viewPath)) {
            // Fallback to simple HTML if view doesn't exist
            return new Response(
                '<h1>404 Not Found</h1><p>The requested URL was not found on this server.</p>',
                404,
                ['Content-Type' => 'text/html; charset=UTF-8']
            );
        }

        ob_start();
        extract([
            'code' => 404,
            'message' => 'The page you are looking for could not be found.',
        ]);
        require $viewPath;
        $html = ob_get_clean();

        return new Response($html, 404, ['Content-Type' => 'text/html; charset=UTF-8']);
    }
}
