<?php

use Illuminate\Support\Facades\Route;

require __DIR__ . '/administration/auth.php';

/**
 * Rutas tenant-aware
 * Aquí SÍ debe existir current tenant.
 */
Route::middleware([
    'bearer_cookie',
    'auth:api',
    'setLocale',
    'resolve_tenant_from_token',
    'tenant',
    'verified.email',
])->prefix('v1')->group(function () {
    require base_path('routes/administration/role.php');
    require base_path('routes/administration/permission.php');
    require base_path('routes/administration/user.php');
    require base_path('routes/administration/audit_log.php');
    require base_path('routes/administration/group_chat.php');
    require base_path('routes/administration/direct_chat.php');
    require base_path('routes/administration/tenant.php');
    require base_path('routes/administration/notifications.php');
    require base_path('routes/administration/chat_presence.php');
});
