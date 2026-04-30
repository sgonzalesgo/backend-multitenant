<?php


use App\Http\Controllers\Academic\SubjectTypeController;
use Illuminate\Support\Facades\Route;

Route::controller(SubjectTypeController::class)->group(function () {
    Route::get('subject-types', 'index')->middleware('permission:List subject_types');
    Route::get('subject-types/active', 'active')->middleware('permission:Search subject_types');
    Route::post('subject-types', 'store')->middleware('permission:Store subject_types');
    Route::get('subject-types/{subjectType}', 'show')->middleware('permission:List subject_types');
    Route::put('subject-types/{subjectType}', 'update')->middleware('permission:Update subject_types');
    Route::delete('subject-types/{subjectType}', 'destroy')->middleware('permission:Delete subject_types');
});
