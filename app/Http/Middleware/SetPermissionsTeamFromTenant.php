<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\Administration\Tenant;
use Spatie\Permission\PermissionRegistrar;

class SetPermissionsTeamFromTenant
{
    public function handle($request, Closure $next)
    {
        if ($tenant = Tenant::current()) {
            // Establece el team_id para Spatie Permission (roles/permisos “scoped”)
            app(PermissionRegistrar::class)->setPermissionsTeamId($tenant->id);
        }

        return $next($request);
    }
}

