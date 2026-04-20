<?php


use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Academic\EnrollmentStatusController;

Route::controller(EnrollmentStatusController::class)->group(function () {
    Route::get('enrollment-statuses', 'index')->middleware('permission:List enrollment_statuses');
    Route::post('enrollment-statuses', 'store')->middleware('permission:Store enrollment_statuses');
    Route::get('enrollment-statuses/{enrollmentStatus}', 'show')->middleware('permission:List enrollment_statuses');
    Route::put('enrollment-statuses/{enrollmentStatus}', 'update')->middleware('permission:Update enrollment_statuses');
    Route::delete('enrollment-statuses/{enrollmentStatus}', 'destroy')->middleware('permission:Delete enrollment_statuses');
});
