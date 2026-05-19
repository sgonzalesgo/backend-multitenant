<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Dashboard\StudentDashboardController;

Route::controller(StudentDashboardController::class)->group(function () {
    Route::post('my-student-dashboard/dashboard', 'myDashboard')->middleware('permission:View student_dashboards');

    Route::post('student-dashboard/{student}/dashboard', 'show')->middleware('permission:View student_dashboards');
});
