<?php


use App\Http\Controllers\Academic\GradeComponentDefinitionController;
use Illuminate\Support\Facades\Route;

Route::controller(GradeComponentDefinitionController::class)->group(function () {
    Route::get('grade-component-definitions', 'index')->middleware('permission:List grade_component_definitions');
    Route::post('grade-component-definitions', 'store')->middleware('permission:Store grade_component_definitions');
    Route::get('grade-component-definitions/{gradeComponentDefinition}', 'show')->middleware('permission:View grade_component_definitions');
    Route::put('grade-component-definitions/{gradeComponentDefinition}', 'update')->middleware('permission:Update grade_component_definitions');
    Route::delete('grade-component-definitions/{gradeComponentDefinition}', 'destroy')->middleware('permission:Delete grade_component_definitions');
});
