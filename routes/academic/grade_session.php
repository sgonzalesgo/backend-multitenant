<?php


use App\Http\Controllers\Academic\QuantitativeEvaluation\GradeSessionController;
use Illuminate\Support\Facades\Route;

Route::controller(GradeSessionController::class)->group(function () {
    Route::post('grade-sessions/open', 'open')->middleware('permission:Open grade_entries');
    Route::get('grade-sessions', 'index')->middleware('permission:List grade_entries');  // esto solo sera para los administradores
    Route::get('grade-sessions/{gradeSession}', 'show')->middleware('permission:Search grade_entries');
    Route::put('grade-sessions/{gradeSession}/save-grades', 'saveGrades')->middleware('permission:Store grade_entries');
    Route::put('grade-sessions/{gradeSession}/close', 'close')->middleware('permission:Close grade_entries');
    Route::put('grade-sessions/{gradeSession}/reopen', 'reopen')->middleware('permission:Reopen grade_entries');
});
