<?php


use App\Http\Controllers\Academic\CourseController;
use Illuminate\Support\Facades\Route;

Route::controller(CourseController::class)->group(function () {
    Route::get('courses/active', 'active')->middleware('permission:Search courses');

    // CRUD
    Route::get('courses', 'index')->middleware('permission:List courses');
    Route::post('courses', 'store')->middleware('permission:Store courses');
    Route::get('courses/{course}', 'show')->middleware('permission:List courses');
    Route::post('courses/{course}', 'update')->middleware('permission:Update courses');
    Route::delete('courses/{course}', 'destroy')->middleware('permission:Delete courses');
});
