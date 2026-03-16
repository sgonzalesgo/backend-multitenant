<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\Administration\Tenant;
use Spatie\Permission\PermissionRegistrar;

class SetPermissionsTeamFromTenant
{
    public function handle($request, Closure $next)
    {
        $tenant = Tenant::current();

        if ($tenant) {
            app(PermissionRegistrar::class)->setPermissionsTeamId($tenant->id);
        }

        return $next($request);
    }
}
