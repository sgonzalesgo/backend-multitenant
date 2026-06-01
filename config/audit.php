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


use App\Models\Academic\AcademicSchedule;
use App\Models\Academic\AcademicYear;
use App\Models\Academic\Classroom;
use App\Models\Academic\Course;
use App\Models\Academic\EducationalLevel;
use App\Models\Academic\Enrollment;
use App\Models\Academic\EnrollmentStatus;
use App\Models\Academic\EvaluationPeriod;
use App\Models\Academic\EvaluationType;
use App\Models\Academic\GradeComponentDefinition;
use App\Models\Academic\GradeComponentTemplate;
use App\Models\Academic\Instructor;
use App\Models\Academic\LegalRepresentative;
use App\Models\Academic\Modality;
use App\Models\Academic\Parallel;
use App\Models\Academic\Shift;
use App\Models\Academic\Specialty;
use App\Models\Academic\Student;
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
use App\Models\General\AcademicNonWorkingDay;
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
        'parallels' => Parallel::class,
        'specialties' => Specialty::class,
        'classrooms' => Classroom::class,
        'educational_levels' => EducationalLevel::class,
        'subject_types' => SubjectType::class,
        'evaluation_types' => EvaluationType::class,
        'subjects' => Subject::class,
        'instructors' => Instructor::class,
        'students' => Student::class,
        'legal_representatives' => LegalRepresentative::class,
        'courses' => Course::class,
        'enrollments' => Enrollment::class,
        'academic_schedules' => AcademicSchedule::class,
        'academic_non_working_days' => AcademicNonWorkingDay::class,
        'grade_component_definitions' => GradeComponentDefinition::class,
        'grade_component_templates' => GradeComponentTemplate::class,
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
        Parallel::class,
        Specialty::class,
        Classroom::class,
        EducationalLevel::class,
        SubjectType::class,
        EvaluationType::class,
        Subject::class,
        Instructor::class,
        Student::class,
        LegalRepresentative::class,
        Course::class,
        Enrollment::class,
        AcademicSchedule::class,
        AcademicNonWorkingDay::class,
        GradeComponentDefinition::class,
        GradeComponentTemplate::class,

        // OJO:
        // TenantPosition NO va aquí porque sus logs los manejas manualmente
        // en TenantPositionRepository.
    ],
];
