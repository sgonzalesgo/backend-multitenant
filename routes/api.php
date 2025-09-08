<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

require __DIR__ . '/administration/auth.php';

// Requieren por obligacion un current tenant
Route::middleware(['auth:api','setLocale','tenant','verified.email'])->prefix('v1')->group(function () {
    require base_path('routes/administration/role.php');
    require base_path('routes/administration/permission.php');
    require base_path('routes/administration/user.php');
    require base_path('routes/administration/audit_log.php');
});

