<?php

namespace Arpon\Http\Middleware;

use Closure;
use Arpon\Contracts\Auth\MustVerifyEmail;
use Arpon\Support\Facades\Redirect;
use Arpon\Support\Facades\URL;

class EnsureEmailIsVerified
{
    /**
     * Handle an incoming request.
     *
     * @param  \Arpon\Http\Request  $request
     * @param  \Closure  $next
     * @param  string|null  $redirectToRoute
     * @return \Arpon\Http\Response|\Arpon\Http\RedirectResponse|null
     */
    public function handle($request, Closure $next, $redirectToRoute = null)
    {
        if (! $request->user() ||
            ($request->user() instanceof MustVerifyEmail &&
            ! $request->user()->hasVerifiedEmail())) {
            return $request->expectsJson()
                    ? response()->json(['message' => 'Your email address is not verified.'], 403)
                    : Redirect::guest($redirectToRoute ?: URL::route('verification.notice'));
        }

        return $next($request);
    }
}
