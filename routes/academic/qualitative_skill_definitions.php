<?php


use App\Http\Controllers\Academic\QualitativeEvaluation\QualitativeSkillDefinitionController;
use Illuminate\Support\Facades\Route;

Route::controller(QualitativeSkillDefinitionController::class)->group(function () {
    Route::get('qualitative-skill-definitions', 'index')->middleware('permission:List qualitative_skill_definitions');
    Route::get('qualitative-skill-definitions/active', 'active')->middleware('permission:Search qualitative_skill_definitions');
    Route::post('qualitative-skill-definitions', 'store')->middleware('permission:Store qualitative_skill_definitions');
    Route::get('qualitative-skill-definitions/{qualitativeSkillDefinition}', 'show')->middleware('permission:List qualitative_skill_definitions');
    Route::put('qualitative-skill-definitions/{qualitativeSkillDefinition}', 'update')->middleware('permission:Update qualitative_skill_definitions');
    Route::delete('qualitative-skill-definitions/{qualitativeSkillDefinition}', 'destroy')->middleware('permission:Delete qualitative_skill_definitions');
});
