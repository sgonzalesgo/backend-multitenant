<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Administration\UserController;

Route::controller(UserController::class)->group(function () {
    Route::get('users',               'index')->middleware('permission:List users');
    Route::post('users',              'store')->middleware('permission:Create users');
    Route::get('users/{user}',        'show')->middleware('permission:List users');
    Route::put('users/{user}',        'update')->middleware('permission:Update users');
    Route::delete('users/{user}',     'destroy')->middleware('permission:Delete users');
});
