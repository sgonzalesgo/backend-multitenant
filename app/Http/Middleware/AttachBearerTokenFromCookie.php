<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AttachBearerTokenFromCookie
{
    public function handle(Request $request, Closure $next)
    {

        $existingAuthorization = $request->header('Authorization');

        if (!empty($existingAuthorization)) {
            return $next($request);
        }

        $cookieName = (string) config('auth.cookie', 'access_token');
        $token = $request->cookie($cookieName);

        if (is_string($token)) {
            $token = trim($token);
        }

        if (!empty($token)) {
            $request->headers->set('Authorization', 'Bearer ' . $token);
        }

        return $next($request);
    }
}
