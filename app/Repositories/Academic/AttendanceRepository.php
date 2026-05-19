<?php

namespace App\Repositories\Academic;

use App\Models\Academic\AttendanceRecord;
use App\Models\Academic\AttendanceSession;
use App\Models\Academic\Enrollment;
use App\Models\Academic\Instructor;
use App\Models\Administration\Tenant;
use App\Models\Calendar\CalendarEvent;
use App\Models\General\AcademicNonWorkingDay;
use Carbon\CarbonImmutable;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Collection;

class AttendanceRepository
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

        return $token?->tenant_id ? (string) $token->tenant_id : null;
    }

    /**
     * @throws ValidationException
     */
    protected function requireTenantId(): string
    {
        $tenantId = $this->resolveCurrentTenantId();

        if (! $tenantId) {
            throw ValidationException::withMessages([
                'tenant' => __('messages.attendance.tenant_not_resolved'),
            ]);
        }

        return $tenantId;
    }

    /**
     * @throws ValidationException
     */
    protected function ensureInstructorExists(string $instructorId): void
    {
        $exists = Instructor::query()
            ->where('id', $instructorId)
            ->exists();

        if (! $exists) {
            throw ValidationException::withMessages([
                'instructor_id' => __('messages.attendance.instructor_not_found'),
            ]);
        }
    }

    /**
     * Retorna las combinaciones disponibles para pasar asistencia:
     *
     * tenant + academic_year + course + parallel + subject + instructor
     * @throws ValidationException
     */
    public function mySubjects(array $filters = []): array
    {
        $tenantId = $this->requireTenantId();

        $instructorId = Arr::get($filters, 'instructor_id');

        if (! $instructorId) {
            throw ValidationException::withMessages([
                'instructor_id' => __('validation.required', [
                    'attribute' => __('validation.attributes.instructor_id'),
                ]),
            ]);
        }

        $this->ensureInstructorExists($instructorId);

        $academicYearId = Arr::get($filters, 'academic_year_id');

        $events = CalendarEvent::query()
            ->with([
                'eventType:id,code,name,color,icon',
            ])
            ->where('tenant_id', $tenantId)
            ->where('source', 'academic_schedule')
            ->where('metadata->instructor_id', (string) $instructorId)
            ->when($academicYearId, fn ($query) => $query->where('metadata->academic_year_id', $academicYearId))
            ->orderBy('start_at')
            ->get();

        return $events
            ->groupBy(fn (CalendarEvent $event) => implode('|', [
                data_get($event->metadata, 'academic_year_id'),
                data_get($event->metadata, 'course_id'),
                data_get($event->metadata, 'parallel_id'),
                data_get($event->metadata, 'subject_id'),
                data_get($event->metadata, 'instructor_id'),
            ]))
            ->map(function ($group) use ($tenantId) {
                /** @var CalendarEvent $event */
                $event = $group->first();

                $academicYearId = data_get($event->metadata, 'academic_year_id');
                $courseId = data_get($event->metadata, 'course_id');
                $parallelId = data_get($event->metadata, 'parallel_id');
                $subjectId = data_get($event->metadata, 'subject_id');
                $instructorId = data_get($event->metadata, 'instructor_id');

                $closedSessions = AttendanceSession::query()
                    ->where('tenant_id', $tenantId)
                    ->where('academic_year_id', $academicYearId)
                    ->where('course_id', $courseId)
                    ->where('parallel_id', $parallelId)
                    ->where('subject_id', $subjectId)
                    ->where('instructor_id', $instructorId)
                    ->where('status', 'closed')
                    ->count();

                return [
                    'academic_year_id' => $academicYearId,
                    'course_id' => $courseId,
                    'parallel_id' => $parallelId,
                    'subject_id' => $subjectId,
                    'instructor_id' => $instructorId,

                    'total_days' => $group->count(),
                    'closed_days' => $closedSessions,
                    'pending_days' => max($group->count() - $closedSessions, 0),

                    'first_date' => optional($group->min('start_at'))->toDateString(),
                    'last_date' => optional($group->max('start_at'))->toDateString(),
                ];
            })
            ->values()
            ->toArray();
    }

    /**
     * Lista los días/eventos disponibles para una combinación:
     *
     * academic_year + course + parallel + subject + instructor
     *
     * Solo el primer día pendiente queda habilitado.
     */
    public function days(array $data): array
    {
        $tenantId = $this->requireTenantId();

        $instructorId = (string) Arr::get($data, 'instructor_id');

        $this->ensureInstructorExists($instructorId);

        $events = CalendarEvent::query()
            ->where('tenant_id', $tenantId)
            ->where('source', 'academic_schedule')
            ->where('metadata->academic_year_id', Arr::get($data, 'academic_year_id'))
            ->where('metadata->course_id', Arr::get($data, 'course_id'))
            ->where('metadata->parallel_id', Arr::get($data, 'parallel_id'))
            ->where('metadata->shift_id', Arr::get($data, 'shift_id'))
            ->where('metadata->subject_id', Arr::get($data, 'subject_id'))
            ->where('metadata->instructor_id', $instructorId)
            ->when(
                Arr::get($data, 'specialty_id'),
                fn ($query, $value) => $query->where('metadata->specialty_id', $value)
            )
            ->when(
                Arr::get($data, 'modality_id'),
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
            Arr::get($data, 'academic_year_id'),
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

    /**
     * Abre/carga la asistencia de un día específico.
     *
     * Busca el evento internamente usando:
     * academic_year + course + parallel + subject + instructor + attendance_date
     */
    public function openDay(array $data): AttendanceSession
    {
        $tenantId = $this->requireTenantId();

        $instructorId = (string) Arr::get($data, 'instructor_id');

        $this->ensureInstructorExists($instructorId);

        return DB::transaction(function () use ($data, $tenantId, $instructorId) {
            $event = $this->findEventForAttendanceDay($data, $tenantId, $instructorId);

            $existingSession = AttendanceSession::query()
                ->where('tenant_id', $tenantId)
                ->where('calendar_event_id', $event->id)
                ->first();

            if ($existingSession) {
                $this->syncDraftRecordsWithActiveEnrollments($existingSession);

                return $existingSession->refresh()->load($this->relations());
            }

            $this->ensureDayIsEnabled($event, $data, $tenantId, $instructorId);

            $attendanceDate = CarbonImmutable::parse(Arr::get($data, 'attendance_date'))->toDateString();

            $academicScheduleId = data_get($event->metadata, 'academic_schedule_id');
            $academicScheduleFrequencyId = data_get($event->metadata, 'academic_schedule_frequency_id');

            $session = AttendanceSession::query()->firstOrCreate(
                [
                    'tenant_id' => $tenantId,
                    'calendar_event_id' => $event->id,
                ],
                [
                    'academic_schedule_id' => $academicScheduleId,
                    'academic_schedule_frequency_id' => $academicScheduleFrequencyId,

                    'academic_year_id' => data_get($event->metadata, 'academic_year_id') ?? Arr::get($data, 'academic_year_id'),
                    'course_id' => data_get($event->metadata, 'course_id') ?? Arr::get($data, 'course_id'),
                    'specialty_id' => data_get($event->metadata, 'specialty_id') ?? Arr::get($data, 'specialty_id'),
                    'parallel_id' => data_get($event->metadata, 'parallel_id') ?? Arr::get($data, 'parallel_id'),
                    'modality_id' => data_get($event->metadata, 'modality_id') ?? Arr::get($data, 'modality_id'),
                    'shift_id' => data_get($event->metadata, 'shift_id') ?? Arr::get($data, 'shift_id'),
                    'subject_id' => data_get($event->metadata, 'subject_id') ?? Arr::get($data, 'subject_id'),
                    'instructor_id' => data_get($event->metadata, 'instructor_id') ?? $instructorId,

                    'attendance_date' => $attendanceDate,
                    'status' => 'draft',
                    'created_by' => auth()->id(),
                ]
            );

            $this->syncDraftRecordsWithActiveEnrollments($session);

            return $session->refresh()->load($this->relations());
        });
    }

    /**
     * Guarda o cierra una sesión de asistencia.
     * @throws ValidationException
     */
    public function save(AttendanceSession $session, array $data): AttendanceSession
    {
        $tenantId = $this->requireTenantId();

        if ((string) $session->tenant_id !== $tenantId) {
            abort(404);
        }

        $instructorId = (string) Arr::get($data, 'instructor_id');

        $this->ensureInstructorExists($instructorId);

        if ((string) $session->instructor_id !== $instructorId) {
            throw ValidationException::withMessages([
                'instructor_id' => __('messages.attendance.instructor_does_not_match_session'),
            ]);
        }

        if ($session->status === 'closed') {
            throw ValidationException::withMessages([
                'attendance_session' => __('messages.attendance.session_already_closed'),
            ]);
        }

        return DB::transaction(function () use ($session, $data) {
            $session->fill([
                'observation' => Arr::get($data, 'observation'),
            ]);

            foreach (Arr::get($data, 'records', []) as $recordData) {
                $status = Arr::get($recordData, 'status');

                AttendanceRecord::query()
                    ->where('tenant_id', $session->tenant_id)
                    ->where('attendance_session_id', $session->id)
                    ->where('enrollment_id', Arr::get($recordData, 'enrollment_id'))
                    ->update([
                        'status' => $status,
                        'late_minutes' => $status === 'late'
                            ? (int) Arr::get($recordData, 'late_minutes', 0)
                            : 0,
                        'observation' => Arr::get($recordData, 'observation'),
                    ]);
            }

            if ((bool) Arr::get($data, 'close', false)) {
                $session->status = 'closed';
                $session->closed_at = now();
            }

            $session->save();

            return $session->refresh()->load($this->relations());
        });
    }

    /**
     * Historial de asistencia por:
     *
     * academic_year + course + specialty + parallel + modality + shift + subject + instructor
     */
    public function records(array $data): array
    {
        $tenantId = $this->requireTenantId();

        $instructorId = (string) Arr::get($data, 'instructor_id');

        $this->ensureInstructorExists($instructorId);

        $sessions = AttendanceSession::query()
            ->with([
                'academicYear:id,name,start_date,end_date',
                'course:id,name,code',
                'specialty:id,name,code',
                'parallel:id,name,code',
                'modality:id,name,code',
                'shift:id,name,code',
                'subject:id,name,code',
                'instructor:id,person_id',
                'instructor.person:id,full_name,email,photo',
                'calendarEvent:id,title,start_at,end_at',
                'records:id,attendance_session_id,enrollment_id,student_id,person_id,status,late_minutes,observation',
                'records.person:id,full_name,email,legal_id,photo',
                'records.student:id,person_id,student_code,status',
                'records.enrollment:id,enrollment_code,student_id,academic_year_id,course_id,specialty_id,parallel_id,modality_id,shift_id,is_active',
            ])
            ->where('tenant_id', $tenantId)
            ->where('academic_year_id', Arr::get($data, 'academic_year_id'))
            ->where('course_id', Arr::get($data, 'course_id'))
            ->where('parallel_id', Arr::get($data, 'parallel_id'))
            ->where('modality_id', Arr::get($data, 'modality_id'))
            ->where('shift_id', Arr::get($data, 'shift_id'))
            ->where('subject_id', Arr::get($data, 'subject_id'))
            ->where('instructor_id', $instructorId)
            ->when(
                Arr::get($data, 'specialty_id'),
                fn ($query, $value) => $query->where('specialty_id', $value),
                fn ($query) => $query->whereNull('specialty_id')
            )
            ->when(
                Arr::get($data, 'from_date'),
                fn ($query, $value) => $query->whereDate('attendance_date', '>=', $value)
            )
            ->when(
                Arr::get($data, 'to_date'),
                fn ($query, $value) => $query->whereDate('attendance_date', '<=', $value)
            )
            ->orderBy('attendance_date')
            ->get();

        if (Arr::get($data, 'status')) {
            $sessions->each(function (AttendanceSession $session) use ($data) {
                $session->setRelation(
                    'records',
                    $session->records
                        ->where('status', Arr::get($data, 'status'))
                        ->values()
                );
            });
        }

        return $sessions
            ->map(function (AttendanceSession $session) {
                return [
                    'attendance_session_id' => (string) $session->id,
                    'calendar_event_id' => (string) $session->calendar_event_id,

                    'attendance_date' => optional($session->attendance_date)?->toDateString(),
                    'status' => $session->status,
                    'closed_at' => optional($session->closed_at)?->toIso8601String(),
                    'observation' => $session->observation,

                    'academic_year' => $session->academicYear ? [
                        'id' => (string) $session->academicYear->id,
                        'name' => $session->academicYear->name,
                    ] : null,

                    'course' => $session->course ? [
                        'id' => (string) $session->course->id,
                        'code' => $session->course->code,
                        'name' => $session->course->name,
                    ] : null,

                    'specialty' => $session->specialty ? [
                        'id' => (string) $session->specialty->id,
                        'code' => $session->specialty->code,
                        'name' => $session->specialty->name,
                    ] : null,

                    'parallel' => $session->parallel ? [
                        'id' => (string) $session->parallel->id,
                        'code' => $session->parallel->code,
                        'name' => $session->parallel->name,
                    ] : null,

                    'modality' => $session->modality ? [
                        'id' => (string) $session->modality->id,
                        'code' => $session->modality->code,
                        'name' => $session->modality->name,
                    ] : null,

                    'shift' => $session->shift ? [
                        'id' => (string) $session->shift->id,
                        'code' => $session->shift->code,
                        'name' => $session->shift->name,
                    ] : null,

                    'subject' => $session->subject ? [
                        'id' => (string) $session->subject->id,
                        'code' => $session->subject->code,
                        'name' => $session->subject->name,
                    ] : null,

                    'instructor' => $session->instructor ? [
                        'id' => (string) $session->instructor->id,
                        'full_name' => $session->instructor->person?->full_name,
                        'email' => $session->instructor->person?->email,
                        'photo' => $session->instructor->person?->photo,
                    ] : null,

                    'calendar_event' => $session->calendarEvent ? [
                        'id' => (string) $session->calendarEvent->id,
                        'title' => $session->calendarEvent->title,
                        'start_at' => optional($session->calendarEvent->start_at)?->toIso8601String(),
                        'end_at' => optional($session->calendarEvent->end_at)?->toIso8601String(),
                    ] : null,

                    'records' => $session->records
                        ->map(function (AttendanceRecord $record) {
                            return [
                                'id' => (string) $record->id,

                                'enrollment_id' => (string) $record->enrollment_id,
                                'student_id' => (string) $record->student_id,
                                'person_id' => (string) $record->person_id,

                                'enrollment_code' => $record->enrollment?->enrollment_code,
                                'student_code' => $record->student?->student_code,

                                'student_name' => $record->person?->full_name,
                                'email' => $record->person?->email,
                                'legal_id' => $record->person?->legal_id,
                                'photo' => $record->person?->photo,

                                'status' => $record->status,
                                'late_minutes' => (int) $record->late_minutes,
                                'observation' => $record->observation,
                            ];
                        })
                        ->values()
                        ->toArray(),
                ];
            })
            ->values()
            ->toArray();
    }

    /**
     * Busca el evento real generado por AcademicSchedule para un día específico.
     * @throws ValidationException
     */
    protected function findEventForAttendanceDay(array $data, string $tenantId, string $instructorId): CalendarEvent
    {
        if (Arr::get($data, 'calendar_event_id')) {
            $event = CalendarEvent::query()
                ->where('tenant_id', $tenantId)
                ->where('source', 'academic_schedule')
                ->where('id', Arr::get($data, 'calendar_event_id'))
                ->where('metadata->instructor_id', $instructorId)
                ->first();

            if ($event) {
                return $event;
            }
        }

        throw ValidationException::withMessages([
            'attendance_date' => __('messages.attendance.event_not_found'),
        ]);
    }

    /**
     * Valida que el día seleccionado sea el primer día pendiente.
     * @throws ValidationException
     */
    protected function ensureDayIsEnabled(CalendarEvent $selectedEvent, array $data, string $tenantId, string $instructorId): void {
        $days = $this->days([
            'academic_year_id' => data_get($selectedEvent->metadata, 'academic_year_id') ?? Arr::get($data, 'academic_year_id'),
            'course_id' => data_get($selectedEvent->metadata, 'course_id') ?? Arr::get($data, 'course_id'),
            'specialty_id' => data_get($selectedEvent->metadata, 'specialty_id') ?? Arr::get($data, 'specialty_id'),
            'parallel_id' => data_get($selectedEvent->metadata, 'parallel_id') ?? Arr::get($data, 'parallel_id'),
            'modality_id' => data_get($selectedEvent->metadata, 'modality_id') ?? Arr::get($data, 'modality_id'),
            'shift_id' => data_get($selectedEvent->metadata, 'shift_id') ?? Arr::get($data, 'shift_id'),
            'subject_id' => data_get($selectedEvent->metadata, 'subject_id') ?? Arr::get($data, 'subject_id'),
            'instructor_id' => data_get($selectedEvent->metadata, 'instructor_id') ?? $instructorId,
        ]);

        $selectedDay = collect($days)->firstWhere(
            'calendar_event_id',
            (string) $selectedEvent->id
        );

        if (! $selectedDay || ! $selectedDay['is_enabled']) {
            throw ValidationException::withMessages([
                'attendance_date' => __('messages.attendance.day_not_enabled'),
            ]);
        }
    }

    /**
     * Crea los records faltantes desde enrollments activos.
     */
    protected function createMissingRecords(AttendanceSession $session): void
    {
        $existingEnrollmentIds = AttendanceRecord::query()
            ->where('tenant_id', $session->tenant_id)
            ->where('attendance_session_id', $session->id)
            ->pluck('enrollment_id')
            ->map(fn ($id) => (string) $id)
            ->all();

        $enrollments = Enrollment::query()
            ->with('student.person')
            ->where('tenant_id', $session->tenant_id)
            ->where('academic_year_id', $session->academic_year_id)
            ->where('course_id', $session->course_id)
            ->where('parallel_id', $session->parallel_id)
            ->when(
                $session->specialty_id,
                fn ($query, $value) => $query->where('specialty_id', $value),
                fn ($query) => $query->whereNull('specialty_id')
            )
            ->when(
                $session->modality_id,
                fn ($query, $value) => $query->where('modality_id', $value)
            )
            ->where('shift_id', $session->shift_id)
            ->where('is_active', true)
            ->whereNotIn('id', $existingEnrollmentIds)
            ->get();

        foreach ($enrollments as $enrollment) {
            if (! $enrollment->student || ! $enrollment->student->person) {
                continue;
            }

            AttendanceRecord::query()->create([
                'tenant_id' => $session->tenant_id,
                'attendance_session_id' => $session->id,
                'enrollment_id' => $enrollment->id,
                'student_id' => $enrollment->student_id,
                'person_id' => $enrollment->student->person->id,
                'status' => 'present',
                'late_minutes' => 0,
                'observation' => null,
            ]);
        }
    }

    protected function relations(): array
    {
        return [
            'academicYear:id,name,start_date,end_date',
            'course:id,name,code',
            'specialty:id,name,code',
            'parallel:id,name,code',
            'modality:id,name,code',
            'shift:id,name,code',
            'subject:id,name,code',

            'instructor:id,person_id',
            'instructor.person:id,full_name,email,photo',

            'calendarEvent:id,title,start_at,end_at,metadata',

            'records:id,attendance_session_id,enrollment_id,student_id,person_id,status,late_minutes,observation',
            'records.student:id,person_id,student_code,status',
            'records.person:id,full_name,email,photo,legal_id',
            'records.enrollment:id,enrollment_code,student_id,academic_year_id,course_id,specialty_id,parallel_id,modality_id,shift_id,is_active',
        ];
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

    /**
     * Sincroniza los records de una sesión en borrador con las matrículas activas actuales.
     *
     * Si una matrícula dejó de tener enrollment_status = active, se elimina de la sesión draft.
     * Si una matrícula activa no tiene record, se crea.
     */
    protected function syncDraftRecordsWithActiveEnrollments(AttendanceSession $session): void
    {
        if ($session->status === 'closed') {
            return;
        }

        $activeEnrollments = Enrollment::query()
            ->with('student.person')
            ->where('tenant_id', $session->tenant_id)
            ->where('academic_year_id', $session->academic_year_id)
            ->where('course_id', $session->course_id)
            ->where('parallel_id', $session->parallel_id)
            ->when(
                $session->specialty_id,
                fn ($query, $value) => $query->where('specialty_id', $value),
                fn ($query) => $query->whereNull('specialty_id')
            )
            ->where('modality_id', $session->modality_id)
            ->where('shift_id', $session->shift_id)
            ->whereHas('enrollmentStatus', function ($query) {
                $query->where('code', 'active');
            })
            ->get();

        $activeEnrollmentIds = $activeEnrollments
            ->pluck('id')
            ->map(fn ($id) => (string) $id)
            ->all();

        AttendanceRecord::query()
            ->where('tenant_id', $session->tenant_id)
            ->where('attendance_session_id', $session->id)
            ->when(
                ! empty($activeEnrollmentIds),
                fn ($query) => $query->whereNotIn('enrollment_id', $activeEnrollmentIds),
                fn ($query) => $query
            )
            ->delete();

        $existingEnrollmentIds = AttendanceRecord::query()
            ->where('tenant_id', $session->tenant_id)
            ->where('attendance_session_id', $session->id)
            ->pluck('enrollment_id')
            ->map(fn ($id) => (string) $id)
            ->all();

        foreach ($activeEnrollments as $enrollment) {
            if (! $enrollment->student || ! $enrollment->student->person) {
                continue;
            }

            if (in_array((string) $enrollment->id, $existingEnrollmentIds, true)) {
                continue;
            }

            AttendanceRecord::query()->create([
                'tenant_id' => $session->tenant_id,
                'attendance_session_id' => $session->id,
                'enrollment_id' => $enrollment->id,
                'student_id' => $enrollment->student_id,
                'person_id' => $enrollment->student->person->id,
                'status' => 'present',
                'late_minutes' => 0,
                'observation' => null,
            ]);
        }
    }

    /**
     * Reabre una sesión de asistencia cerrada.
     *
     * Solo administradores o usuarios autorizados deberían poder ejecutar esto.
     */
    public function reopen(AttendanceSession $session): AttendanceSession
    {
        $tenantId = $this->requireTenantId();

        if ((string) $session->tenant_id !== $tenantId) {
            abort(404);
        }

        if ($session->status !== 'closed') {
            throw ValidationException::withMessages([
                'attendance_session' => __('messages.attendance.session_is_not_closed'),
            ]);
        }

        $session->update([
            'status' => 'draft',
            'closed_at' => null,
        ]);

        $this->syncDraftRecordsWithActiveEnrollments($session);

        return $session->refresh()->load($this->relations());
    }
}
