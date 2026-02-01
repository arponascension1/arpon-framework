<?php

namespace Arpon\Http\Middleware;

use Closure;
use Arpon\Session\SessionManager;
use Arpon\Http\Request;
use Arpon\Http\Response;

class StartSession
{
    protected $manager;

    public function __construct(SessionManager $manager)
    {
        $this->manager = $manager;
    }

    public function handle(Request $request, Closure $next)
    {
        // Always use the singleton session from the container
        $manager = app('session');

        // Start the session
        $manager->start();

        // Flash old input if available (exclude _token and password fields)
        if ($request->isMethod('POST') || $request->isMethod('PUT') || $request->isMethod('PATCH')) {
            $input = $request->all();
            unset($input['_token'], $input['password'], $input['password_confirmation']);
            $manager->flashInput($input);
        }

        $response = $next($request);

        // Store current URL as previous for the next request
        if ($request->isMethod('GET') && !$request->isAjax()) {
            $manager->put('_previous.url', $request->getFullUrl());
        }

        // Save the session
        $manager->save();

        return $response;
    }
}
