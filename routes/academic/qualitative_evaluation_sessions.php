<?php


use App\Http\Controllers\Academic\QualitativeEvaluation\QualitativeEvaluationSessionController;
use Illuminate\Support\Facades\Route;

Route::controller(QualitativeEvaluationSessionController::class)->group(function () {

    Route::post('qualitative-evaluation-sessions/open', 'open')->middleware('permission:Open grade_entries');
    Route::get('qualitative-evaluation-sessions', 'index')->middleware('permission:List grade_entries');
    Route::get('qualitative-evaluation-sessions/{qualitativeEvaluationSession}', 'show')->middleware('permission:Search grade_entries');
    Route::put('qualitative-evaluation-sessions/save', 'save')->middleware('permission:Store grade_entries');
    Route::put('qualitative-evaluation-sessions/{qualitativeEvaluationSession}/close', 'close')->middleware('permission:Close grade_entries');
    Route::put('qualitative-evaluation-sessions/{qualitativeEvaluationSession}/reopen', 'reopen')->middleware('permission:Reopen grade_entries');
});
