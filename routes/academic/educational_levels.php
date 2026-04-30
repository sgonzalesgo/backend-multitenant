<?php


use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Academic\EducationalLevelController;

Route::controller(EducationalLevelController::class)->group(function () {
    Route::get('educational-levels', 'index')->middleware('permission:List educational_levels');
    Route::get('educational-levels/active', 'active')->middleware('permission:Search educational_levels');
    Route::post('educational-levels', 'store')->middleware('permission:Store educational_levels');
    Route::get('educational-levels/{educationalLevel}', 'show')->middleware('permission:List educational_levels');
    Route::put('educational-levels/{educationalLevel}', 'update')->middleware('permission:Update educational_levels');
    Route::delete('educational-levels/{educationalLevel}', 'destroy')->middleware('permission:Delete educational_levels');
});
