<?php


use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Academic\AcademicYearEvaluationPeriodController;

Route::controller(AcademicYearEvaluationPeriodController::class)->group(function () {
    Route::get('academic-year-evaluation-periods', 'index')->middleware('permission:List academic_year_evaluation_periods');
    Route::post('academic-year-evaluation-periods', 'store')->middleware('permission:Store academic_year_evaluation_periods');
    Route::get('academic-year-evaluation-periods/{academicYearEvaluationPeriod}', 'show')->middleware('permission:List academic_year_evaluation_periods');
    Route::put('academic-year-evaluation-periods/{academicYearEvaluationPeriod}', 'update')->middleware('permission:Update academic_year_evaluation_periods');
    Route::delete('academic-year-evaluation-periods/{academicYearEvaluationPeriod}', 'destroy')->middleware('permission:Delete academic_year_evaluation_periods');
    Route::put('academic-years/{academicYear}/evaluation-periods/sync', 'syncByAcademicYear')->middleware('permission:Update academic_year_evaluation_periods');
});
