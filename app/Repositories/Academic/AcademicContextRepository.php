<?php

namespace App\Repositories\Academic;

use App\Models\Academic\AcademicSchedule;
use App\Models\Academic\AttendanceSession;
use App\Models\Academic\Enrollment;
use App\Models\Administration\Tenant;
use App\Models\Academic\Instructor;
use App\Models\Calendar\CalendarEvent;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use App\Models\General\AcademicNonWorkingDay;

class AcademicContextRepository
{
    protected function resolveCurrentTenantId(): ?string
    {
        if ($current = Tenant::current()) {
            return (string) $current->id;
        }

        $user = auth()->user();

        if (! $user || ! method_exists($user, 'token')) {
            return null;
        }

        $token = $user->token();

        if (! $token || empty($token->tenant_id)) {
            return null;
        }

        return (string) $token->tenant_id;
    }

    public function resolve(array $filters): array
    {
        $tenantId = $this->requireTenantId();

        $schedules = $this->baseScheduleQuery($tenantId, $filters)
            ->with($this->scheduleRelations())
            ->orderBy('created_at', 'desc')
            ->get();

        return [
            'filters' => $filters,

            'educational_levels' => $this->mapEducationalLevels($schedules),
            'courses' => $this->mapCourses($schedules),
            'specialties' => $this->mapSpecialties($schedules),
            'parallels' => $this->mapParallels($schedules),
            'modalities' => $this->mapModalities($schedules),
            'shifts' => $this->mapShifts($schedules),
            'subjects' => $this->mapSubjects($schedules),
            'instructors' => $this->mapInstructors($schedules),

            'students' => $this->getStudents($tenantId, $filters),

            'schedules' => $this->mapSchedules($schedules),
            'frequencies' => $this->mapFrequencies($schedules),

            'attendance_days' => Arr::get($filters, 'context') === 'attendance'
                ? $this->getAttendanceDays($tenantId, $filters)
                : [],
        ];
    }

    protected function baseScheduleQuery(string $tenantId, array $filters)
    {
        $canManageAllAttendances = $this->canManageAllAttendances();

        $authenticatedInstructorId = $this->resolveAuthenticatedInstructorId();

        if (! $canManageAllAttendances && ! $authenticatedInstructorId) {
            return AcademicSchedule::query()->whereRaw('1 = 0');
        }

        return AcademicSchedule::query()
            ->where('tenant_id', $tenantId)
            ->where('academic_year_id', Arr::get($filters, 'academic_year_id'))
            ->whereIn('status', ['draft', 'in_progress', 'accepted'])

            ->when(Arr::get($filters, 'educational_level_id'), function ($query, $value) {
                $query->whereHas('course', function ($q) use ($value) {
                    $q->where('educational_level_id', $value);
                });
            })

            ->when(Arr::get($filters, 'course_id'), fn ($query, $value) => $query->where('course_id', $value))

            ->when(Arr::get($filters, 'specialty_id'), fn ($query, $value) => $query->where('specialty_id', $value))

            ->when(Arr::get($filters, 'parallel_id'), fn ($query, $value) => $query->where('parallel_id', $value))

            ->when(Arr::get($filters, 'modality_id'), fn ($query, $value) => $query->where('modality_id', $value))

            ->when(Arr::get($filters, 'shift_id'), fn ($query, $value) => $query->where('shift_id', $value))

            ->when(Arr::get($filters, 'subject_id'), function ($query, $value) {
                $query->whereHas('frequencies', function ($q) use ($value) {
                    $q->where('subject_id', $value);
                });
            })

            ->when(
                ! $canManageAllAttendances,
                function ($query) use ($authenticatedInstructorId) {
                    $query->whereHas('frequencies', function ($q) use ($authenticatedInstructorId) {
                        $q->where('instructor_id', $authenticatedInstructorId);
                    });
                }
            )

            ->when(
                Arr::get($filters, 'instructor_id'),
                function ($query, $value) use ($canManageAllAttendances, $authenticatedInstructorId) {
                    $instructorId = $canManageAllAttendances
                        ? $value
                        : $authenticatedInstructorId;

                    $query->whereHas('frequencies', function ($q) use ($instructorId) {
                        $q->where('instructor_id', $instructorId);
                    });
                }
            );
    }

    protected function getStudents(string $tenantId, array $filters): Collection
    {
        return Enrollment::query()
            ->with([
                'student:id,tenant_id,person_id,student_code,status',
                'student.person:id,full_name,email,phone,legal_id,photo',
                'course:id,tenant_id,educational_level_id,code,name,level_number,status',
                'specialty:id,tenant_id,code,name,is_active',
                'parallel:id,tenant_id,code,name,is_active',
                'modality:id,tenant_id,code,name,is_active',
                'shift:id,tenant_id,code,name,is_active',
            ])
            ->where('tenant_id', $tenantId)
            ->where('academic_year_id', Arr::get($filters, 'academic_year_id'))
            ->where('is_active', true)

            ->when(Arr::get($filters, 'educational_level_id'), function ($query, $value) {
                $query->whereHas('course', function ($q) use ($value) {
                    $q->where('educational_level_id', $value);
                });
            })

            ->when(Arr::get($filters, 'course_id'), fn ($query, $value) => $query->where('course_id', $value))
            ->when(Arr::get($filters, 'specialty_id'), fn ($query, $value) => $query->where('specialty_id', $value))
            ->when(Arr::get($filters, 'parallel_id'), fn ($query, $value) => $query->where('parallel_id', $value))
            ->when(Arr::get($filters, 'modality_id'), fn ($query, $value) => $query->where('modality_id', $value))
            ->when(Arr::get($filters, 'shift_id'), fn ($query, $value) => $query->where('shift_id', $value))
            ->when(Arr::get($filters, 'student_id'), fn ($query, $value) => $query->where('student_id', $value))

            ->whereHas('student.person', function ($query) {
                $query->whereNull('deleted_at')
                    ->whereNull('deceased_at');
            })

            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function (Enrollment $enrollment) {
                return [
                    'id' => $enrollment->student?->id,
                    'student_code' => $enrollment->student?->student_code,
                    'full_name' => $enrollment->student?->person?->full_name,
                    'email' => $enrollment->student?->person?->email,
                    'legal_id' => $enrollment->student?->person?->legal_id,
                    'photo' => $enrollment->student?->person?->photo,

                    'enrollment_id' => $enrollment->id,
                    'academic_year_id' => $enrollment->academic_year_id,
                    'course_id' => $enrollment->course_id,
                    'specialty_id' => $enrollment->specialty_id,
                    'parallel_id' => $enrollment->parallel_id,
                    'modality_id' => $enrollment->modality_id,
                    'shift_id' => $enrollment->shift_id,
                ];
            })
            ->values();
    }

    protected function getAttendanceDays(string $tenantId, array $filters): array
    {
        $events = CalendarEvent::query()
            ->where('tenant_id', $tenantId)
            ->where('source', 'academic_schedule')
            ->where('metadata->academic_year_id', Arr::get($filters, 'academic_year_id'))
            ->where('metadata->course_id', Arr::get($filters, 'course_id'))
            ->where('metadata->parallel_id', Arr::get($filters, 'parallel_id'))
            ->where('metadata->shift_id', Arr::get($filters, 'shift_id'))
            ->where('metadata->subject_id', Arr::get($filters, 'subject_id'))
            ->where('metadata->instructor_id', Arr::get($filters, 'instructor_id'))
            ->when(
                Arr::get($filters, 'specialty_id'),
                fn ($query, $value) => $query->where('metadata->specialty_id', $value)
            )
            ->when(
                Arr::get($filters, 'modality_id'),
                fn ($query, $value) => $query->where('metadata->modality_id', $value)
            )
            ->orderBy('start_at')
            ->get();

        $sessions = AttendanceSession::query()
            ->where('tenant_id', $tenantId)
            ->whereIn('calendar_event_id', $events->pluck('id'))
            ->get()
            ->keyBy(fn (AttendanceSession $session) => (string) $session->calendar_event_id);

        $nonWorkingDays = $this->getNonWorkingDaysForAttendance(
            $tenantId,
            Arr::get($filters, 'academic_year_id'),
            $events
        );

        $firstPendingFound = false;

        return $events
            ->map(function (CalendarEvent $event) use ($sessions, $nonWorkingDays, &$firstPendingFound) {
                $date = optional($event->start_at)?->toDateString();

                $nonWorkingDay = $date
                    ? $nonWorkingDays->get($date)
                    : null;

                $isNonWorkingDay = $nonWorkingDay !== null;

                $session = $sessions->get((string) $event->id);

                $status = $isNonWorkingDay
                    ? 'non_working_day'
                    : ($session?->status ?? 'pending');

                $enabled = false;

                if (
                    ! $isNonWorkingDay &&
                    $status !== 'closed' &&
                    ! $firstPendingFound
                ) {
                    $enabled = true;
                    $firstPendingFound = true;
                }

                return [
                    'calendar_event_id' => (string) $event->id,
                    'attendance_session_id' => $session?->id ? (string) $session->id : null,

                    'academic_schedule_id' => data_get($event->metadata, 'academic_schedule_id'),
                    'academic_schedule_frequency_id' => data_get($event->metadata, 'academic_schedule_frequency_id'),

                    'academic_year_id' => data_get($event->metadata, 'academic_year_id'),
                    'course_id' => data_get($event->metadata, 'course_id'),
                    'specialty_id' => data_get($event->metadata, 'specialty_id'),
                    'parallel_id' => data_get($event->metadata, 'parallel_id'),
                    'modality_id' => data_get($event->metadata, 'modality_id'),
                    'shift_id' => data_get($event->metadata, 'shift_id'),
                    'subject_id' => data_get($event->metadata, 'subject_id'),
                    'instructor_id' => data_get($event->metadata, 'instructor_id'),

                    'date' => $date,
                    'start_at' => optional($event->start_at)?->toIso8601String(),
                    'end_at' => optional($event->end_at)?->toIso8601String(),

                    'title' => $event->title,
                    'status' => $status,
                    'is_enabled' => $enabled,

                    'is_non_working_day' => $isNonWorkingDay,
                    'non_working_day' => $nonWorkingDay ? [
                        'id' => (string) $nonWorkingDay->id,
                        'name' => $nonWorkingDay->name,
                        'type' => $nonWorkingDay->type,
                        'observation' => $nonWorkingDay->observation,
                    ] : null,

                    'closed_at' => optional($session?->closed_at)?->toIso8601String(),
                ];
            })
            ->values()
            ->toArray();
    }

    protected function scheduleRelations(): array
    {
        return [
            'academicYear:id,name,start_date,end_date,is_active,is_current',
            'course:id,tenant_id,educational_level_id,code,name,level_number,status',
            'course.educationalLevel:id,tenant_id,code,name,has_specialty',
            'specialty:id,tenant_id,code,name,is_active',
            'parallel:id,tenant_id,code,name,is_active',
            'modality:id,tenant_id,code,name,is_active',
            'shift:id,tenant_id,code,name,is_active',

            'frequencies:id,academic_schedule_id,day_of_week,start_time,end_time,classroom_id,subject_id,instructor_id,observation',
            'frequencies.classroom:id,tenant_id,code,name,capacity,location,is_active',
            'frequencies.subject:id,tenant_id,code,name,is_active',
            'frequencies.instructor:id,tenant_id,person_id,department_id,academic_title,academic_level,status',
            'frequencies.instructor.person:id,full_name,email,phone,photo',
        ];
    }

    protected function mapEducationalLevels($schedules)
    {
        return $schedules
            ->pluck('course.educationalLevel')
            ->filter()
            ->unique('id')
            ->map(fn ($item) => [
                'id' => $item->id,
                'code' => $item->code,
                'name' => $item->name,
                'has_specialty' => $item->has_specialty,
            ])
            ->values();
    }

    protected function mapCourses($schedules)
    {
        return $schedules
            ->pluck('course')
            ->filter()
            ->unique('id')
            ->map(fn ($item) => [
                'id' => $item->id,
                'code' => $item->code,
                'name' => $item->name,
                'educational_level_id' => $item->educational_level_id,
                'level_number' => $item->level_number,
            ])
            ->values();
    }

    protected function mapSpecialties($schedules)
    {
        return $schedules
            ->pluck('specialty')
            ->filter()
            ->unique('id')
            ->map(fn ($item) => [
                'id' => $item->id,
                'code' => $item->code,
                'name' => $item->name,
            ])
            ->values();
    }

    protected function mapParallels($schedules)
    {
        return $schedules
            ->pluck('parallel')
            ->filter()
            ->unique('id')
            ->map(fn ($item) => [
                'id' => $item->id,
                'code' => $item->code,
                'name' => $item->name,
            ])
            ->values();
    }

    protected function mapModalities($schedules)
    {
        return $schedules
            ->pluck('modality')
            ->filter()
            ->unique('id')
            ->map(fn ($item) => [
                'id' => $item->id,
                'code' => $item->code,
                'name' => $item->name,
            ])
            ->values();
    }

    protected function mapShifts($schedules)
    {
        return $schedules
            ->pluck('shift')
            ->filter()
            ->unique('id')
            ->map(fn ($item) => [
                'id' => $item->id,
                'code' => $item->code,
                'name' => $item->name,
            ])
            ->values();
    }

    protected function mapSubjects($schedules)
    {
        return $schedules
            ->flatMap(fn ($schedule) => $schedule->frequencies)
            ->pluck('subject')
            ->filter()
            ->unique('id')
            ->map(fn ($item) => [
                'id' => $item->id,
                'code' => $item->code,
                'name' => $item->name,
            ])
            ->values();
    }

    protected function mapInstructors($schedules)
    {
        $canManageAllAttendances = $this->canManageAllAttendances();
        $authenticatedInstructorId = $this->resolveAuthenticatedInstructorId();

        return $schedules
            ->flatMap(fn ($schedule) => $schedule->frequencies)
            ->pluck('instructor')
            ->filter()
            ->when(
                ! $canManageAllAttendances && $authenticatedInstructorId,
                fn ($collection) => $collection->filter(
                    fn ($item) => (string) $item->id === (string) $authenticatedInstructorId
                )
            )
            ->unique('id')
            ->map(fn ($item) => [
                'id' => $item->id,
                'full_name' => $item->person?->full_name,
                'email' => $item->person?->email,
                'photo' => $item->person?->photo,
                'academic_title' => $item->academic_title,
            ])
            ->values();
    }

    protected function mapSchedules($schedules)
    {
        return $schedules
            ->map(function (AcademicSchedule $schedule) {
                return [
                    'id' => $schedule->id,
                    'academic_year_id' => $schedule->academic_year_id,
                    'course_id' => $schedule->course_id,
                    'specialty_id' => $schedule->specialty_id,
                    'parallel_id' => $schedule->parallel_id,
                    'modality_id' => $schedule->modality_id,
                    'shift_id' => $schedule->shift_id,
                    'status' => $schedule->status,
                ];
            })
            ->values();
    }

    protected function mapFrequencies($schedules)
    {
        return $schedules
            ->flatMap(function (AcademicSchedule $schedule) {
                return $schedule->frequencies->map(function ($frequency) use ($schedule) {
                    return [
                        'id' => $frequency->id,
                        'academic_schedule_id' => $schedule->id,

                        'course_id' => $schedule->course_id,
                        'specialty_id' => $schedule->specialty_id,
                        'parallel_id' => $schedule->parallel_id,
                        'modality_id' => $schedule->modality_id,
                        'shift_id' => $schedule->shift_id,

                        'day_of_week' => $frequency->day_of_week,
                        'start_time' => $this->formatTime($frequency->start_time),
                        'end_time' => $this->formatTime($frequency->end_time),

                        'classroom_id' => $frequency->classroom_id,
                        'classroom_name' => $frequency->classroom?->name,

                        'subject_id' => $frequency->subject_id,
                        'subject_name' => $frequency->subject?->name,

                        'instructor_id' => $frequency->instructor_id,
                        'instructor_name' => $frequency->instructor?->person?->full_name,
                    ];
                });
            })
            ->values();
    }

    /**
     * @throws ValidationException
     */
    protected function requireTenantId(): string
    {
        $tenantId = $this->resolveCurrentTenantId();

        if (! $tenantId) {
            throw ValidationException::withMessages([
                'tenant' => __('messages.academic_context.tenant_not_resolved'),
            ]);
        }

        return $tenantId;
    }

    protected function formatTime($value): ?string
    {
        if (! $value) {
            return null;
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format('H:i');
        }

        return substr((string) $value, 0, 5);
    }

    protected function getNonWorkingDaysForAttendance(string $tenantId, ?string $academicYearId, Collection $events): Collection {
        $dates = $events
            ->map(fn (CalendarEvent $event) => optional($event->start_at)?->toDateString())
            ->filter()
            ->unique()
            ->values();

        if ($dates->isEmpty()) {
            return collect();
        }

        return AcademicNonWorkingDay::query()
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->where('affects_attendance', true)
            ->whereIn('date', $dates)
            ->where(function ($query) use ($academicYearId) {
                $query->whereNull('academic_year_id');

                if ($academicYearId) {
                    $query->orWhere('academic_year_id', $academicYearId);
                }
            })
            ->orderByDesc('academic_year_id')
            ->get()
            ->keyBy(fn (AcademicNonWorkingDay $day) => $day->date->format('Y-m-d'));
    }

    protected function canManageAllAttendances(): bool
    {
        $user = auth()->user();

        return $user && $user->can('Manage all_attendances');
    }

    protected function resolveAuthenticatedInstructorId(): ?string
    {
        $user = auth()->user();

        if (! $user) {
            return null;
        }

        return Instructor::query()
            ->where('tenant_id', $this->requireTenantId())
            ->where('person_id', $user->person_id)
            ->value('id');
    }
}
