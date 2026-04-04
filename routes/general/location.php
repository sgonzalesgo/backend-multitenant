<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\General\LocationController;


Route::prefix('v1')->group(function () {
    Route::middleware(['setLocale'])->group(function () {
        Route::controller(LocationController::class)->group(function () {
            Route::get('locations/countries', 'countries');
            Route::get('locations/states', 'states');
            Route::get('locations/cities', 'cities');
        });
    });
});



