<?php


use Illuminate\Support\Facades\Route;
use App\Http\Controllers\General\AcademicNonWorkingDayController;

Route::controller(AcademicNonWorkingDayController::class)->group(function () {

    Route::get('academic-non-working-days/active', 'active')->middleware('permission:Search academic_non_working_days');
    Route::get('academic-non-working-days', 'index')->middleware('permission:List academic_non_working_days');
    Route::post('academic-non-working-days', 'store')->middleware('permission:Store academic_non_working_days');
    Route::get('academic-non-working-days/{academicNonWorkingDay}', 'show')->middleware('permission:Search academic_non_working_days');
    Route::post('academic-non-working-days/{academicNonWorkingDay}', 'update')->middleware('permission:Update academic_non_working_days');
    Route::delete('academic-non-working-days/{academicNonWorkingDay}', 'destroy')->middleware('permission:Delete academic_non_working_days');
});
