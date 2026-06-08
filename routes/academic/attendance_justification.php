<?php

use App\Http\Controllers\Academic\AttendanceJustificationController;
use Illuminate\Support\Facades\Route;


Route::controller(AttendanceJustificationController::class)->group(function () {

    Route::get(
        'attendance-justifications/pending-records',
        'pendingRecords'
    )->middleware('permission:List attendance_justifications');

    Route::get(
        'attendance-justifications',
        'index'
    )->middleware('permission:List attendance_justifications');

    Route::post(
        'attendance-justifications',
        'store'
    )->middleware('permission:Store attendance_justifications');

    Route::post(
        'attendance-justifications/{attendanceJustification}/approve',
        'approve'
    )->middleware('permission:Approve attendance_justifications');

    Route::post(
        'attendance-justifications/{attendanceJustification}/reject',
        'reject'
    )->middleware('permission:Reject attendance_justifications');

    Route::post(
        'attendance-justifications/{attendanceJustification}/document',
        'uploadDocument'
    )->middleware('permission:Update attendance_justifications');

    Route::delete(
        'attendance-justifications/{attendanceJustification}',
        'destroy'
    )->middleware('permission:Delete attendance_justifications');
});
