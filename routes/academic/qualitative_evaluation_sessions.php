<?php


use App\Http\Controllers\Academic\QualitativeEvaluation\QualitativeEvaluationSessionController;
use Illuminate\Support\Facades\Route;

Route::controller(QualitativeEvaluationSessionController::class)->group(function () {

    Route::post('qualitative-evaluation-sessions/open', 'open')->middleware('permission:Open qualitative_evaluation_sessions');
    Route::get('qualitative-evaluation-sessions/{qualitativeEvaluationSession}', 'show')->middleware('permission:View qualitative_evaluation_sessions');
    Route::put('qualitative-evaluation-sessions/save', 'save')->middleware('permission:Update qualitative_evaluation_sessions');
    Route::put('qualitative-evaluation-sessions/{qualitativeEvaluationSession}/close', 'close')->middleware('permission:Close qualitative_evaluation_sessions');
    Route::put('qualitative-evaluation-sessions/{qualitativeEvaluationSession}/reopen', 'reopen')->middleware('permission:Reopen qualitative_evaluation_sessions');
});
