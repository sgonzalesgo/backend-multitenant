<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\General\PersonController;

Route::controller(PersonController::class)->group(function () {
    Route::post('persons/lookup-by-legal-id', 'lookupByLegalId')->middleware('permission:List persons');
    Route::get('persons', 'index')->middleware('permission:List persons');
    Route::post('persons', 'store')->middleware('permission:Store persons');
    Route::get('persons/{person}', 'show')->middleware('permission:List persons');
    Route::post('persons/{person}', 'update')->middleware('permission:Update persons');
    Route::delete('persons/{person}', 'destroy')->middleware('permission:Delete persons');
});
