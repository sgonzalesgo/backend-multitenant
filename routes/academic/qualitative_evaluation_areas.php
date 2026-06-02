<?php


use App\Http\Controllers\Academic\QualitativeEvaluation\QualitativeEvaluationAreaController;
use Illuminate\Support\Facades\Route;

Route::controller(QualitativeEvaluationAreaController::class)->group(function () {
    Route::get('qualitative-evaluation-areas', 'index')->middleware('permission:List qualitative_evaluation_areas');
    Route::get('qualitative-evaluation-areas/active', 'active')->middleware('permission:Search qualitative_evaluation_areas');
    Route::post('qualitative-evaluation-areas', 'store')->middleware('permission:Store qualitative_evaluation_areas');
    Route::get('qualitative-evaluation-areas/{qualitativeEvaluationArea}', 'show')->middleware('permission:List qualitative_evaluation_areas');
    Route::put('qualitative-evaluation-areas/{qualitativeEvaluationArea}', 'update')->middleware('permission:Update qualitative_evaluation_areas');
    Route::delete('qualitative-evaluation-areas/{qualitativeEvaluationArea}', 'destroy')->middleware('permission:Delete qualitative_evaluation_areas');
});
