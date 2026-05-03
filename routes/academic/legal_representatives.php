<?php


use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Academic\LegalRepresentativeController;

Route::controller(LegalRepresentativeController::class)->group(function () {
    Route::get('legal-representatives/active', 'active')->middleware('permission:Search legal_representatives');

    // CRUD
    Route::get('legal-representatives', 'index')->middleware('permission:List legal_representatives');
    Route::post('legal-representatives', 'store')->middleware('permission:Store legal_representatives');
    Route::get('legal-representatives/{legalRepresentative}', 'show')->middleware('permission:List legal_representatives');
    Route::post('legal-representatives/{legalRepresentative}', 'update')->middleware('permission:Update legal_representatives');
    Route::delete('legal-representatives/{legalRepresentative}', 'destroy')->middleware('permission:Delete legal_representatives');
});
