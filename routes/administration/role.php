<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Administration\RoleController;

Route::controller(RoleController::class)->group(function () {

    // CRUD
    Route::get('roles',                 'index')->middleware('permission:List roles');
    Route::post('roles',                'store')->middleware('permission:Create roles');
    Route::get('roles/{role}',          'show')->middleware('permission:List roles');
    Route::put('roles/{role}',          'update')->middleware('permission:Update roles');
    Route::delete('roles/{role}',       'destroy')->middleware('permission:Delete roles');


    // Permisos del rol
    Route::get('roles/{role}/permissions',       'listPermissions')->middleware('permission:List permissions');
    Route::post('roles/{role}/permissions/sync', 'syncPermissions')->middleware('permission:Update permissions');

    // Asignación de roles a usuario en tenant (por IDs)
    Route::post('roles/sync-user-roles',         'syncUserRoles')->middleware('permission:Assign roles');
});







