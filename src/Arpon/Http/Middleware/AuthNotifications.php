<?php

namespace Arpon\Http\Middleware;

use Arpon\Http\Request;
use Arpon\Support\Facades\Auth;

class AuthNotifications
{
    /**
     * Handle an incoming request.
     *
     * @param  \Arpon\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, $next)
    {
        $response = $next($request);

        // Only process for authenticated users
        if (Auth::check()) {
            $user = Auth::user();
            
            // Check if user needs email verification
            if ($user instanceof \Arpon\Contracts\Auth\MustVerifyEmail && !$user->hasVerifiedEmail()) {
                // You could add logic here to show verification reminder
                // or automatically send verification notification after certain actions
            }
        }

        return $response;
    }
}
