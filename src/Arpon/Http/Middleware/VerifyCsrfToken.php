<?php

namespace Arpon\Http\Middleware;

use Closure;
use Arpon\Session\SessionManager;
use Arpon\Http\Request;
use Arpon\Http\Response;
use Exception;

class VerifyCsrfToken
{
    protected $manager;
    protected $except = [];

    public function __construct(SessionManager $manager)
    {
        $this->manager = $manager;
    }

    public function handle(Request $request, Closure $next)
    {
        // Check if the request is too large (POST data was truncated)
        if ($this->requestExceedsMaxSize($request)) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'The uploaded file is too large.'], 413);
            }
            abort(413, 'The uploaded file is too large.');
        }
        
        if ($this->isReading($request)) {
            return $next($request);
        }
        
        if ($this->shouldPassThrough($request)) {
            return $next($request);
        }

        if (!$this->tokensMatch($request)) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'CSRF token mismatch. Please refresh the page and try again.'], 419);
            }
            abort(419, 'CSRF token mismatch. Please refresh the page and try again.');
        }

        return $next($request);
    }

    protected function isReading(Request $request)
    {
        return in_array($request->getMethod(), ['HEAD', 'GET', 'OPTIONS']);
    }

    protected function shouldPassThrough(Request $request)
    {
        foreach ($this->except as $pattern) {
            if ($request->is($pattern)) {
                return true;
            }
        }

        return false;
    }

    protected function tokensMatch(Request $request)
    {
        // Get token from session/cookie
        $token = $this->manager->token();

        // Get token from request (form field, header, or cookie)
        $requestToken = $request->input('_token') ?: $request->header('X-CSRF-TOKEN');

        if (!$requestToken) {
            $requestToken = $request->header('X-XSRF-TOKEN');
        }
        


        if (!$requestToken || !$token) {
            return false;
        }

        return hash_equals($token, $requestToken);
    }

    protected function requestExceedsMaxSize(Request $request)
    {
        $method = $request->getMethod();
        
        // Only check for POST, PUT, PATCH methods
        if (!in_array($method, ['POST', 'PUT', 'PATCH'])) {
            return false;
        }

        // Get the content length from headers
        $contentLength = $request->header('Content-Length');
        
        if (!$contentLength) {
            return false;
        }

        // Get post_max_size from PHP config
        $postMaxSize = $this->iniGetBytes('post_max_size');
        
        // Check if content length exceeds post_max_size
        if ($postMaxSize > 0 && $contentLength > $postMaxSize) {
            return true;
        }
        
        // If POST data is empty but Content-Length is set and it's form data (not JSON), request was too large
        $contentType = $request->header('Content-Type');
        $isFormData = strpos($contentType, 'application/x-www-form-urlencoded') !== false || 
                      strpos($contentType, 'multipart/form-data') !== false;
        
        if ($isFormData && $contentLength > 0 && empty($_POST) && empty($_FILES)) {
            return true;
        }

        return false;
    }

    protected function iniGetBytes($setting)
    {
        $val = ini_get($setting);
        
        if (empty($val)) {
            return 0;
        }

        $val = trim($val);
        $last = strtolower($val[strlen($val) - 1]);
        $val = (int) $val;

        switch ($last) {
            case 'g':
                $val *= 1024;
            case 'm':
                $val *= 1024;
            case 'k':
                $val *= 1024;
        }

        return $val;
    }
}
