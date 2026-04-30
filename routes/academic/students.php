<?php


use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Academic\StudentController;


Route::controller(StudentController::class)->group(function () {
    Route::get('students/active', 'active')->middleware('permission:Search students');
    // CRUD
    Route::get('students', 'index')->middleware('permission:List students');
    Route::post('students', 'store')->middleware('permission:Store students');
    Route::get('students/{student}', 'show')->middleware('permission:List students');
    Route::post('students/{student}', 'update')->middleware('permission:Update students');
    Route::delete('students/{student}', 'destroy')->middleware('permission:Delete students');
});
