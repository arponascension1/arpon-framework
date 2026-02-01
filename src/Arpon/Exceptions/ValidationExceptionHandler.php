<?php

namespace Arpon\Exceptions;

use Arpon\Http\RedirectResponse;
use Arpon\Http\Response;
use Arpon\Validation\ValidationException;

class ValidationExceptionHandler
{
    protected $app;
    protected $config;

    public function __construct($app, $config)
    {
        $this->app = $app;
        $this->config = $config;
    }

    /**
     * Handle validation exception.
     * 
     * @param ValidationException $e
     * @param mixed $request
     * @return Response|RedirectResponse
     */
    public function handle(ValidationException $e, mixed $request): Response|RedirectResponse
    {
        // Check for custom renderable handlers first
        $renderables = $this->config->getRenderables() ?? [];
        
        foreach ($renderables as $renderable) {
            $result = $renderable($e, $request);
            if ($result !== null) {
                // Custom handler returned a response, save session and return it
                $this->saveSession();
                return $result;
            }
        }
        
        // Default validation exception handling for JSON requests
        if (method_exists($request, 'expectsJson') && $request->expectsJson()) {
            return (new Response())->json([
                'message' => 'The given data was invalid.',
                'errors' => $e->errors()->toArray(),
            ], 422);
        }

        // Default validation exception handling for web requests
        $response = redirect()->back()
            ->withInput($request->except(['password', 'password_confirmation']))
            ->withErrors($e->errors());
        
        // IMPORTANT: Save session now because middleware already ran
        // When an exception is thrown, the StartSession middleware's save() never runs
        $this->saveSession();
        
        return $response;
    }

    /**
     * Save session if available.
     */
    protected function saveSession(): void
    {
        if ($this->app->bound('session')) {
            $session = $this->app->make('session');
            $session->save();
        }
    }
}
