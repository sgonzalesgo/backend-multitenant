<?php


use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Academic\ModalityController;

Route::controller(ModalityController::class)->group(function () {
    Route::get('modalities', 'index')->middleware('permission:List modalities');
    Route::get('modalities/active', 'active')->middleware('permission:Search modalities');
    Route::post('modalities', 'store')->middleware('permission:Store modalities');
    Route::get('modalities/{modality}', 'show')->middleware('permission:List modalities');
    Route::put('modalities/{modality}', 'update')->middleware('permission:Update modalities');
    Route::delete('modalities/{modality}', 'destroy')->middleware('permission:Delete modalities');
});
