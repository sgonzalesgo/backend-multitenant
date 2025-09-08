<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AttachBearerTokenFromCookie
{
    public function handle(Request $request, Closure $next)
    {
        $cookieName = config('auth.cookie', 'access_token');

        if ($token = $request->cookie($cookieName)) {
            $request->headers->set('Authorization', 'Bearer ' . $token);
        }

        return $next($request);
    }
}
