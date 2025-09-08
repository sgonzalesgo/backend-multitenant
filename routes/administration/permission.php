<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Administration\PermissionController;

Route::controller(PermissionController::class)->group(function () {
    Route::get('permissions',                 'index')->middleware('permission:List permissions');
    Route::post('permissions',                'store')->middleware('permission:Create permissions');
    Route::get('permissions/{permission}',    'show')->middleware('permission:List permissions');
    Route::put('permissions/{permission}',    'update')->middleware('permission:Update permissions');
    Route::delete('permissions/{permission}', 'destroy')->middleware('permission:Delete permissions');

    Route::get('roles/{role}/permissions',             'listRolePermissions')->middleware('permission:List permissions');
    Route::post('roles/{role}/permissions/sync',       'syncRolePermissions')->middleware('permission:Update permissions');
});







