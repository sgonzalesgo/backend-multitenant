<?php


use App\Http\Controllers\Academic\GradeComponentTemplateController;
use Illuminate\Support\Facades\Route;

Route::controller(GradeComponentTemplateController::class)->group(function () {
    Route::get('grade-component-templates', 'index')->middleware('permission:List grade_component_templates');
    Route::post('grade-component-templates', 'store')->middleware('permission:Store grade_component_templates');
    Route::get('grade-component-templates/{gradeComponentTemplate}', 'show')->middleware('permission:View grade_component_templates');
    Route::put('grade-component-templates/{gradeComponentTemplate}', 'update')->middleware('permission:Update grade_component_templates');
    Route::delete('grade-component-templates/{gradeComponentTemplate}', 'destroy')->middleware('permission:Delete grade_component_templates');
    Route::post('grade-component-templates/{gradeComponentTemplate}/generate-components', 'generateComponents')->middleware('permission:Generate grade_components');
});
