<?php


use App\Http\Controllers\Academic\QualitativeEvaluation\QualitativeEvaluationComponentController;
use Illuminate\Support\Facades\Route;

Route::controller(QualitativeEvaluationComponentController::class)->group(function () {
    Route::get('qualitative-evaluation-components', 'index')->middleware('permission:List qualitative_evaluation_components');
    Route::post('qualitative-evaluation-components/generate', 'generate')->middleware('permission:Store qualitative_evaluation_components');
    Route::get('qualitative-evaluation-components/{qualitativeEvaluationComponent}', 'show')->middleware('permission:List qualitative_evaluation_components');
    Route::delete('qualitative-evaluation-components/{qualitativeEvaluationComponent}', 'destroy')->middleware('permission:Delete qualitative_evaluation_components');
    Route::delete('qualitative-evaluation-components/group/delete', 'destroyGroup')->middleware('permission:Delete qualitative_evaluation_components');
});
