<?php


use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Academic\AcademicContextController;

Route::controller(AcademicContextController::class)->group(function () {

    Route::post('academic-context/resolve', 'resolve')->middleware('permission:Search academic_context');
});
