<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

require __DIR__ . '/administration/auth.php';

Route::middleware(['auth:api','tenant','setLocale'])->prefix('v1')->group(function () {
    require base_path('routes/administration/permission.php');
    require base_path('routes/administration/role.php');
//    require base_path('routes/v1/administration/user.php');
});
