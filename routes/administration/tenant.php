<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Administration\TenantController;

Route::controller(TenantController::class)->group(function () {
    Route::get('tenants', 'index')->middleware('permission:List tenants');
    Route::get('tenants/status/{status}', 'indexByStatus')->middleware('permission:Search tenants');
    Route::get('tenants/{id}', 'show')->middleware('permission:Search tenants');
    Route::post('tenants', 'store')->middleware('permission:Create tenants');
    Route::post('tenants/{id}', 'update')->middleware('permission:Update tenants');
});
