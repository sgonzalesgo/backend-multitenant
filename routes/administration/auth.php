<?php

use App\Http\Controllers\Administration\AuthController;

use Illuminate\Support\Facades\Route;


Route::prefix('v1')->group(function () {
    // Throttle básico para login
    Route::post('auth/login', [AuthController::class, 'login'])->name('login');
    Route::post('auth/refresh', [AuthController::class, 'refresh'])->middleware('throttle:refresh');
    Route::post('auth/social/upsert', [AuthController::class, 'socialUpsert']);

    // Registro (no requieren auth)
    Route::post('auth/register', [AuthController::class, 'register']);     // email/password
    Route::post('auth/social',   [AuthController::class, 'social']);       // google|facebook

    // Rutas protegidas por Passport (ajusta guard si usas otro)
    Route::middleware(['auth:api','setLocale','tenant','bearer_cookie'])->group(function () {
        Route::get('/auth/me', [AuthController::class, 'me'])->name('me');
        Route::post('/auth/switch-company', [AuthController::class, 'switchTenant'])->name('switchTenant');
        Route::post('/auth/logout', [AuthController::class, 'logout']);
    });
});
