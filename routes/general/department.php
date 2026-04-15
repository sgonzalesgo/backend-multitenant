<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\General\DepartmentController;

Route::controller(DepartmentController::class)->group(function () {
    Route::get('departments', 'index')->middleware('permission:List departments');
    Route::post('departments', 'store')->middleware('permission:Store departments');
    Route::get('departments/{department}', 'show')->middleware('permission:List departments');
    Route::post('departments/{department}', 'update')->middleware('permission:Update departments');
    Route::delete('departments/{department}', 'destroy')->middleware('permission:Delete departments');
});
