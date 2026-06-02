<?php


use App\Http\Controllers\Academic\QualitativeEvaluation\QualitativeEvaluationTemplateController;
use Illuminate\Support\Facades\Route;

Route::controller(QualitativeEvaluationTemplateController::class)->group(function () {
    Route::get('qualitative-evaluation-templates', 'index')->middleware('permission:List qualitative_evaluation_templates');
    Route::get('qualitative-evaluation-templates/active', 'active')->middleware('permission:Search qualitative_evaluation_templates');
    Route::post('qualitative-evaluation-templates', 'store')->middleware('permission:Store qualitative_evaluation_templates');
    Route::get('qualitative-evaluation-templates/{qualitativeEvaluationTemplate}', 'show')->middleware('permission:List qualitative_evaluation_templates');
    Route::put('qualitative-evaluation-templates/{qualitativeEvaluationTemplate}', 'update')->middleware('permission:Update qualitative_evaluation_templates');
    Route::delete('qualitative-evaluation-templates/{qualitativeEvaluationTemplate}', 'destroy')->middleware('permission:Delete qualitative_evaluation_templates');
});
