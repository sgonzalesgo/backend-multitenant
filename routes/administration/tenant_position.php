<?php


use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Administration\TenantPositionController;

Route::controller(TenantPositionController::class)->group(function () {
    Route::get('tenant-positions', 'index')->middleware('permission:List tenant_positions');
    Route::get('tenant-positions/status/{status}', 'indexByStatus')->middleware('permission:Search tenant_positions');
    Route::get('tenant-positions/tenant/{tenantId}', 'indexByTenant')->middleware('permission:Search tenant_positions');
    Route::get('tenant-positions/{id}', 'show')->middleware('permission:Search tenant_positions');
    Route::post('tenant-positions', 'store')->middleware('permission:Store tenant_positions');
    Route::post('tenant-positions/{id}', 'update')->middleware('permission:Update tenant_positions');
});
