<?php

namespace App\Http\Middleware;

use App\Models\Administration\Tenant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveTenantFromToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return $next($request);
        }

        $token = $user->token();

        if (!$token || empty($token->tenant_id)) {
            return $next($request);
        }

        $tenant = Tenant::query()->find($token->tenant_id);

        if ($tenant) {
            $tenant->makeCurrent();
        }

        return $next($request);
    }
}
