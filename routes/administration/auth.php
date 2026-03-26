<?php

use App\Http\Controllers\Administration\AuthController;
use App\Http\Controllers\Administration\EmailVerificationController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    /*
    |--------------------------------------------------------------------------
    | Públicas
    |--------------------------------------------------------------------------
    */
    Route::middleware(['setLocale'])->group(function () {
        Route::post('auth/login', [AuthController::class, 'login'])->name('login');
        Route::post('auth/register', [AuthController::class, 'register']);
        Route::post('auth/refresh', [AuthController::class, 'refresh'])->middleware('throttle:refresh');
        Route::post('auth/social/upsert', [AuthController::class, 'socialUpsert']);
        Route::post('auth/verify/request-code', [EmailVerificationController::class, 'requestCode']);
        Route::post('auth/verify/confirm', [EmailVerificationController::class, 'confirm']);
        Route::post('auth/social/login', [AuthController::class, 'socialLogin']);
    });

    /*
    |--------------------------------------------------------------------------
    | Auth protegida por cookie -> bearer -> auth:api
    |--------------------------------------------------------------------------
    */
    Route::middleware(['bearer_cookie', 'auth:api', 'setLocale'])->group(function () {
        Route::get('auth/me', [AuthController::class, 'me'])->name('me');
        Route::post('auth/logout', [AuthController::class, 'logout'])->name('logout');

        // Cambio de tenant: NO requiere middleware tenant
        // El repositorio valida si el usuario puede cambiar al tenant solicitado.
        Route::post('auth/switch-company', [AuthController::class, 'switchTenant'])->name('switchTenant');

        // Impersonación
        Route::post('auth/impersonate', [AuthController::class, 'impersonate'])->name('impersonate');
        Route::post('auth/impersonate/revert', [AuthController::class, 'revertImpersonation'])->name('revertImpersonation');

        // Heartbeat / presencia
        Route::post('auth/ping', [AuthController::class, 'ping'])->name('ping');
    });
});
