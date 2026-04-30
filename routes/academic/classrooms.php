<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Academic\ClassroomController;

Route::controller(ClassroomController::class)->group(function () {
    Route::get('classrooms', 'index')->middleware('permission:List classrooms');
    Route::get('classrooms/active', 'active')->middleware('permission:Search classrooms');
    Route::post('classrooms', 'store')->middleware('permission:Store classrooms');
    Route::get('classrooms/{classroom}', 'show')->middleware('permission:List classrooms');
    Route::put('classrooms/{classroom}', 'update')->middleware('permission:Update classrooms');
    Route::delete('classrooms/{classroom}', 'destroy')->middleware('permission:Delete classrooms');
});
