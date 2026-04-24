<?php


use App\Http\Controllers\Academic\SpecialtyController;
use Illuminate\Support\Facades\Route;

Route::controller(SpecialtyController::class)->group(function () {
    Route::get('specialties', 'index')->middleware('permission:List specialties');
    Route::get('specialties/active', 'active')->middleware('permission:List specialties');
    Route::post('specialties', 'store')->middleware('permission:Store specialties');
    Route::get('specialties/{specialty}', 'show')->middleware('permission:List specialties');
    Route::put('specialties/{specialty}', 'update')->middleware('permission:Update specialties');
    Route::delete('specialties/{specialty}', 'destroy')->middleware('permission:Delete specialties');
});
