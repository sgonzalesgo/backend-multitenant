<?php
// app/Tenancy/Finders/HeaderTenantFinder.php

namespace App\Tenancy\Finders;

use Illuminate\Http\Request;
use Spatie\Multitenancy\TenantFinder\TenantFinder;
use App\Models\Administration\Tenant;

class HeaderTenantFinder extends TenantFinder
{
    public function findForRequest(Request $request): ?Tenant
    {
        $key = $request->header('tenant_id');
        if (!$key) return null;

        return Tenant::where('id', $key)       // o where('slug', $key), etc.
        ->first();
    }
}
