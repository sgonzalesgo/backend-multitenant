<?php

namespace App\Http\Middleware;

use Closure;
use Spatie\Permission\PermissionRegistrar;

class UseGlobalPermissions
{
    public function handle($request, Closure $next)
    {
        $landlordTeamId = config('permission.landlord_team_id');

        app(PermissionRegistrar::class)->setPermissionsTeamId($landlordTeamId);

        return $next($request);
    }
}
