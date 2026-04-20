<?php


use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Academic\EvaluationPeriodController;

Route::controller(EvaluationPeriodController::class)->group(function () {
    Route::get('evaluation-periods', 'index')->middleware('permission:List evaluation_periods');
    Route::post('evaluation-periods', 'store')->middleware('permission:Store evaluation_periods');
    Route::get('evaluation-periods/{evaluationPeriod}', 'show')->middleware('permission:List evaluation_periods');
    Route::put('evaluation-periods/{evaluationPeriod}', 'update')->middleware('permission:Update evaluation_periods');
    Route::delete('evaluation-periods/{evaluationPeriod}', 'destroy')->middleware('permission:Delete evaluation_periods');
});
