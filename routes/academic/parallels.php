<?php


use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Academic\ParallelController;

Route::controller(ParallelController::class)->group(function () {
    Route::get('parallels', 'index')->middleware('permission:List parallels');
    Route::get('parallels/active', 'active')->middleware('permission:List parallels');
    Route::post('parallels', 'store')->middleware('permission:Store parallels');
    Route::get('parallels/{parallel}', 'show')->middleware('permission:List parallels');
    Route::put('parallels/{parallel}', 'update')->middleware('permission:Update parallels');
    Route::delete('parallels/{parallel}', 'destroy')->middleware('permission:Delete parallels');
});
