<?php
//
//
//use App\Models\Administration\ManualNotification;
//use App\Models\Administration\Permission;
//use App\Models\Administration\Position;
//use App\Models\Administration\Role;
//use App\Models\Administration\Tenant;
//use App\Models\Administration\TenantPosition;
//use App\Models\Administration\User;
//use App\Models\Calendar\CalendarEvent;
//use App\Models\Calendar\CalendarEventType;
//use App\Models\General\Person;
//
//return [
//    'subjects' => [
//        'persons' => Person::class,
//        'users' => User::class,
//        'permissions' => Permission::class,
//        'roles' => Role::class,
//        'calendar_event_types' => CalendarEventType::class,
//        'calendar_events' => CalendarEvent::class,
//        'manual_notifications' => ManualNotification::class,
//        'positions' => Position::class,
//        'tenant_positions' => TenantPosition::class
//    ],
//];


use App\Models\Administration\ManualNotification;
use App\Models\Administration\Permission;
use App\Models\Administration\Position;
use App\Models\Administration\Role;
use App\Models\Administration\Tenant;
use App\Models\Administration\TenantPosition;
use App\Models\Administration\User;
use App\Models\Calendar\CalendarEvent;
use App\Models\Calendar\CalendarEventType;
use App\Models\General\Person;

return [
    /*
    |--------------------------------------------------------------------------
    | Subject aliases
    |--------------------------------------------------------------------------
    | Se usan para resolver el tipo auditable en history y otros endpoints.
    | Aquí pueden estar TODOS los modelos auditables, aunque algunos NO
    | quieran observer automático.
    |--------------------------------------------------------------------------
    */
    'subjects' => [
        'persons' => Person::class,
        'users' => User::class,
        'permissions' => Permission::class,
        'roles' => Role::class,
        'calendar_event_types' => CalendarEventType::class,
        'calendar_events' => CalendarEvent::class,
        'manual_notifications' => ManualNotification::class,
        'positions' => Position::class,
        'tenant_positions' => TenantPosition::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Auto observed models
    |--------------------------------------------------------------------------
    | SOLO estos modelos recibirán auditoría automática vía observer.
    |--------------------------------------------------------------------------
    */
    'auto_observe' => [
        Person::class,
        User::class,
        Permission::class,
        Role::class,
        CalendarEventType::class,
        CalendarEvent::class,
        ManualNotification::class,
        Position::class,

        // OJO:
        // TenantPosition NO va aquí porque sus logs los manejas manualmente
        // en TenantPositionRepository.
    ],
];
