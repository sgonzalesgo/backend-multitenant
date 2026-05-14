<?php


use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Academic\AcademicScheduleController;

Route::controller(AcademicScheduleController::class)->group(function () {

    Route::get('academic-schedules/active', 'active')->middleware('permission:Search academic_schedules');
    // CRUD
    Route::get('academic-schedules', 'index')->middleware('permission:List academic_schedules');
    Route::post('academic-schedules', 'store')->middleware('permission:Store academic_schedules');
    Route::get('academic-schedules/{academicSchedule}', 'show')->middleware('permission:Search academic_schedules');
    Route::post('academic-schedules/{academicSchedule}', 'update')->middleware('permission:Update academic_schedules');
    Route::delete('academic-schedules/{academicSchedule}', 'destroy')->middleware('permission:Delete academic_schedules');
});
