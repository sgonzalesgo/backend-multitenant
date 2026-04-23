<?php

use Illuminate\Support\Facades\Route;

require __DIR__ . '/administration/auth.php';

// locations public
require __DIR__ . '/general/location.php';

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
    require base_path('routes/administration/manual_notifications.php');
    require base_path('routes/calendar/calendar.php');
    require base_path('routes/calendar/calendar_event_type.php');
    require base_path('routes/general/person.php');
    require base_path('routes/academic/instructor.php');
    require base_path('routes/administration/position.php');
    require base_path('routes/administration/tenant_position.php');
    require base_path('routes/general/department.php');
    require base_path('routes/academic/enrollment_statuses.php');
    require base_path('routes/academic/academic_years.php');
    require base_path('routes/academic/evaluation_periods.php');
    require base_path('routes/academic/modalities.php');
    require base_path('routes/academic/shifts.php');
});
