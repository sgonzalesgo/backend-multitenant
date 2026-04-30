<?php


use App\Http\Controllers\Academic\SubjectController;
use Illuminate\Support\Facades\Route;

Route::controller(SubjectController::class)->group(function () {
    Route::get('subjects', 'index')->middleware('permission:List subjects');
    Route::get('subjects/active', 'active')->middleware('permission:Search subjects');
    Route::post('subjects', 'store')->middleware('permission:Store subjects');
    Route::get('subjects/{subject}', 'show')->middleware('permission:List subjects');
    Route::put('subjects/{subject}', 'update')->middleware('permission:Update subjects');
    Route::delete('subjects/{subject}', 'destroy')->middleware('permission:Delete subjects');
});
