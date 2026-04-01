<?php


use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Calendar\CalendarEventTypeController;

Route::controller(CalendarEventTypeController::class)->group(function () {
    Route::get('calendar/event-types', 'index')->middleware('permission:List event_types');
    Route::post('calendar/event-types', 'store')->middleware('permission:Store event_types');
    Route::get('calendar/event-types/{calendarEventType}', 'show')->middleware('permission:Show events_type');
    Route::put('calendar/event-types/{calendarEventType}', 'update')->middleware('permission:Update event_types');
    Route::patch('calendar/event-types/{calendarEventType}', 'update')->middleware('permission:Update event_types');
    Route::delete('calendar/event-types/{calendarEventType}', 'destroy')->middleware('permission:Delete event_types');
});

