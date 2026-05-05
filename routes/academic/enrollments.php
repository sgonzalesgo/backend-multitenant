<?php


use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Academic\EnrollmentController;

Route::controller(EnrollmentController::class)->group(function () {

    Route::get('enrollments/active', 'active')->middleware('permission:Search enrollments');

    // CRUD
    Route::get('enrollments', 'index')->middleware('permission:List enrollments');
    Route::post('enrollments', 'store')->middleware('permission:Store enrollments');
    Route::get('enrollments/{enrollment}', 'show')->middleware('permission:List enrollments');
    Route::post('enrollments/{enrollment}', 'update')->middleware('permission:Update enrollments');
    Route::delete('enrollments/{enrollment}', 'destroy')->middleware('permission:Delete enrollments');
});
