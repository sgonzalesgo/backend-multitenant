<?php


use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Academic\InstructorController;

Route::controller(InstructorController::class)->group(function () {
    Route::get('instructors', 'index')->middleware('permission:List instructors');
    Route::post('instructors', 'store')->middleware('permission:Store instructors');
    Route::get('instructors/{instructor}', 'show')->middleware('permission:List instructors');
    Route::put('instructors/{instructor}', 'update')->middleware('permission:Update instructors');
    Route::delete('instructors/{instructor}', 'destroy')->middleware('permission:Delete instructors');
});
