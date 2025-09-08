<?php


use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Administration\AuditLogController;

Route::controller(AuditLogController::class)->group(function () {
    // Ver auditoría (restringe con tu permiso global “List audit logs”)
    Route::get('audits', 'index')->middleware('permission:List audit_logs');

    // Crear log ad-hoc (opcional; restringe con permiso)
    Route::post('audits', 'store')->middleware('permission:Create audit_logs');
});
