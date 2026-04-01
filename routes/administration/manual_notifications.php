<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Administration\ManualNotificationController;

Route::controller(ManualNotificationController::class)->group(function () {
    Route::get('/admin/manual-notifications', 'index')->middleware('permission:List manual_notifications');
    Route::post('/admin/notifications/send', 'send')->middleware('permission:Send manual_notifications');
    Route::patch('/admin/manual-notifications/{manualNotificationId}/archive', 'archive')->middleware('permission:Archive manual_notifications');
    Route::patch('/admin/manual-notifications/{manualNotificationId}/unarchive', 'unarchive')->middleware('permission:Unarchive manual_notifications');
});

