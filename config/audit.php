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


use App\Models\Academic\AcademicYear;
use App\Models\Academic\Classroom;
use App\Models\Academic\EducationalLevel;
use App\Models\Academic\EnrollmentStatus;
use App\Models\Academic\EvaluationPeriod;
use App\Models\Academic\EvaluationType;
use App\Models\Academic\Modality;
use App\Models\Academic\Shift;
use App\Models\Academic\Specialty;
use App\Models\Academic\Subject;
use App\Models\Academic\SubjectType;
use App\Models\Administration\ManualNotification;
use App\Models\Administration\Permission;
use App\Models\Administration\Position;
use App\Models\Administration\Role;
use App\Models\Administration\Tenant;
use App\Models\Administration\TenantPosition;
use App\Models\Administration\User;
use App\Models\Calendar\CalendarEvent;
use App\Models\Calendar\CalendarEventType;
use App\Models\General\Department;
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
        'departments' => Department::class,
        'enrollment_statuses' => EnrollmentStatus::class,
        'academic_years' => AcademicYear::class,
        'evaluation_periods' => EvaluationPeriod::class,
        'modalities' => Modality::class,
        'shifts' => Shift::class,
        'specialties' => Specialty::class,
        'classrooms' => Classroom::class,
        'educational_levels' => EducationalLevel::class,
        'subject_types' => SubjectType::class,
        'evaluation_types' => EvaluationType::class,
        'subjects' => Subject::class,

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
        Department::class,
        EnrollmentStatus::class,
        AcademicYear::class,
        EvaluationPeriod::class,
        Modality::class,
        Shift::class,
        Specialty::class,
        Classroom::class,
        EducationalLevel::class,
        SubjectType::class,
        EvaluationType::class,
        Subject::class,

        // OJO:
        // TenantPosition NO va aquí porque sus logs los manejas manualmente
        // en TenantPositionRepository.
    ],
];
