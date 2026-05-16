<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Academic\AttendanceController;

Route::prefix('attendance')->controller(AttendanceController::class)->group(function () {
        Route::get('/my-subjects', 'mySubjects')->middleware('permission:List attendances');
        Route::get('/days', 'days')->middleware('permission:List attendances');
        Route::post('/open-day', 'openDay')->middleware('permission:Store attendances');
        Route::post('/{attendanceSession}/save', 'save')->middleware('permission:Update attendances');
        Route::get('/records', 'records')->middleware('permission:Search attendances');
    });
