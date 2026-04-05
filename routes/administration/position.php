<?php


use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Administration\PositionController;

Route::controller(PositionController::class)->group(function () {
    Route::get('positions', 'index')->middleware('permission:List positions');
    Route::get('positions/status/{status}', 'indexByStatus')->middleware('permission:Search positions');
    Route::get('positions/{id}', 'show')->middleware('permission:Search positions');
    Route::post('positions', 'store')->middleware('permission:Store positions');
    Route::post('positions/{id}', 'update')->middleware('permission:Update positions');
});
