<?php


use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Academic\AcademicYearController;

Route::controller(AcademicYearController::class)->group(function () {
    Route::get('academic-years', 'index')->middleware('permission:List academic_years');
    Route::post('academic-years', 'store')->middleware('permission:Store academic_years');
    Route::get('academic-years/{academicYear}', 'show')->middleware('permission:List academic_years');
    Route::put('academic-years/{academicYear}', 'update')->middleware('permission:Update academic_years');
    Route::delete('academic-years/{academicYear}', 'destroy')->middleware('permission:Delete academic_years');
});
