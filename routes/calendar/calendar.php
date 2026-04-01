<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Calendar\CalendarEventController;

Route::controller(CalendarEventController::class)->group(function () {
    Route::get('calendar/events', 'index')->middleware('permission:List events');
    Route::post('calendar/events', 'store')->middleware('permission:Store events');
    Route::get('calendar/events/{calendarEvent}', 'show')->middleware('permission:Show events');
    Route::put('calendar/events/{calendarEvent}', 'update')->middleware('permission:Update events');
    Route::patch('calendar/events/{calendarEvent}', 'update')->middleware('permission:Update events');
    Route::delete('calendar/events/{calendarEvent}', 'destroy')->middleware('permission:Delete events');
});

