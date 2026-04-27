<?php

use App\Http\Controllers\Academic\EvaluationTypeController;
use Illuminate\Support\Facades\Route;

Route::controller(EvaluationTypeController::class)->group(function () {
    Route::get('evaluation-types', 'index')->middleware('permission:List evaluation_types');
    Route::get('evaluation-types/active', 'active')->middleware('permission:List evaluation_types');
    Route::post('evaluation-types', 'store')->middleware('permission:Store evaluation_types');
    Route::get('evaluation-types/{evaluationType}', 'show')->middleware('permission:List evaluation_types');
    Route::put('evaluation-types/{evaluationType}', 'update')->middleware('permission:Update evaluation_types');
    Route::delete('evaluation-types/{evaluationType}', 'destroy')->middleware('permission:Delete evaluation_types');
});
