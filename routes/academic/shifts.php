<?php


use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Academic\ShiftController;

Route::controller(ShiftController::class)->group(function () {
    Route::get('shifts', 'index')->middleware('permission:List shifts');
    Route::get('shifts/active', 'active')->middleware('permission:Search shifts');
    Route::post('shifts', 'store')->middleware('permission:Store shifts');
    Route::get('shifts/{shift}', 'show')->middleware('permission:List shifts');
    Route::put('shifts/{shift}', 'update')->middleware('permission:Update shifts');
    Route::delete('shifts/{shift}', 'destroy')->middleware('permission:Delete shifts');
});
