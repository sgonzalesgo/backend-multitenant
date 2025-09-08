<?php

use App\Http\Controllers\Administration\AuthController;
use App\Http\Controllers\Administration\EmailVerificationController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    // Público
    Route::middleware(['setLocale'])->group(function () {
        Route::post('auth/login', [AuthController::class, 'login'])->name('login');
        Route::post('auth/register', [AuthController::class, 'register']);
        Route::post('auth/refresh', [AuthController::class, 'refresh'])->middleware('throttle:refresh');
        Route::post('auth/social/upsert', [AuthController::class, 'socialUpsert']);
        Route::post('auth/verify/request-code', [EmailVerificationController::class, 'requestCode']);
        Route::post('auth/verify/confirm',      [EmailVerificationController::class, 'confirm']);
    });

// Protegidas con Passport (cookies HttpOnly -> bearer_cookie -> auth:api)
    Route::middleware(['bearer_cookie', 'auth:api', 'setLocale'])->group(function () {
        Route::get('auth/me', [AuthController::class, 'me'])->name('me');
        Route::post('auth/logout', [AuthController::class, 'logout']);

        // Impersonate SIN middleware de permiso (permiso se valida en el repo, opción A)
        Route::post('auth/impersonate', [AuthController::class, 'impersonate']);
        Route::post('auth/impersonate/revert', [AuthController::class, 'revertImpersonation']);
    });

// Si quieres que switch-company exija tenant
    Route::middleware(['bearer_cookie', 'auth:api', 'setLocale', 'tenant'])->group(function () {
        Route::post('auth/switch-company', [AuthController::class, 'switchTenant'])->name('switchTenant');

        // >>> Alternativa si INSISTES en el middleware de permiso en ruta:
        // Route::post('auth/impersonate', [AuthController::class, 'impersonate'])
        //     ->middleware('permission:Impersonate users');
    });
});
