<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureEmailVerified
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        if ($user && is_null($user->email_verified_at)) {
            return response()->json([
                'code' => 403,
                'message' => __('verify.required'),
                'data' => null,
                'error' => 'EmailNotVerified',
            ], 403);
        }
        return $next($request);
    }
}
