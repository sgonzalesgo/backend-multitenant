<?php


use App\Models\Administration\ManualNotification;
use App\Models\Administration\Permission;
use App\Models\Administration\Position;
use App\Models\Administration\Role;
use App\Models\Administration\Tenant;
use App\Models\Administration\User;
use App\Models\Calendar\CalendarEvent;
use App\Models\Calendar\CalendarEventType;
use App\Models\General\Person;

return [
    'subjects' => [
        'persons' => Person::class,
        'users' => User::class,
        'permissions' => Permission::class,
        'roles' => Role::class,
        'calendar_event_types' => CalendarEventType::class,
        'calendar_events' => CalendarEvent::class,
        'manual_notifications' => ManualNotification::class,
        'positions' => Position::class,
    ],
];
