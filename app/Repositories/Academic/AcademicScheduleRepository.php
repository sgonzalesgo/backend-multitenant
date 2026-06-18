<?php

namespace App\Repositories\Academic;


use App\Models\Academic\AcademicSchedule;
use App\Models\Academic\AcademicScheduleFrequency;
use App\Models\Academic\Enrollment;
use App\Models\Administration\Tenant;
use App\Models\Calendar\CalendarEvent;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use App\Models\Calendar\CalendarEventType;

class AcademicScheduleRepository
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

    public function list(array $filters = []): LengthAwarePaginator
    {
        $tenantId = $this->requireTenantId();

        $rawQ = Arr::get($filters, 'q', '');
        $sort = Arr::get($filters, 'sort', 'created_at');
        $dir = strtolower((string) Arr::get($filters, 'dir', 'desc')) === 'asc' ? 'asc' : 'desc';
        $perPage = max(1, min((int) Arr::get($filters, 'per_page', 15), 100));

        if (! in_array($sort, ['created_at', 'updated_at', 'status'], true)) {
            $sort = 'created_at';
        }

        $decodedQ = [];

        if (is_string($rawQ) && trim($rawQ) !== '') {
            $decoded = json_decode($rawQ, true);
            $decodedQ = is_array($decoded) ? $decoded : [];
        } elseif (is_array($rawQ)) {
            $decodedQ = $rawQ;
        }

        $global = trim((string) Arr::get($decodedQ, 'global', ''));
        $columns = Arr::get($decodedQ, 'columns', []);

        return AcademicSchedule::query()
            ->with($this->relations())
            ->where('tenant_id', $tenantId)

            ->when($global !== '', function ($query) use ($global) {
                $query->where(function ($q) use ($global) {
                    $q->where('status', 'ilike', "%{$global}%")
                        ->orWhere('general_observation', 'ilike', "%{$global}%")
                        ->orWhereHas('academicYear', fn ($sq) => $sq->where('name', 'ilike', "%{$global}%"))
                        ->orWhereHas('course', fn ($sq) => $sq->where('name', 'ilike', "%{$global}%"))
                        ->orWhereHas('specialty', fn ($sq) => $sq->where('name', 'ilike', "%{$global}%"))
                        ->orWhereHas('parallel', fn ($sq) => $sq->where('name', 'ilike', "%{$global}%"))
                        ->orWhereHas('modality', fn ($sq) => $sq->where('name', 'ilike', "%{$global}%"))
                        ->orWhereHas('shift', fn ($sq) => $sq->where('name', 'ilike', "%{$global}%"));
                });
            })

            ->when(Arr::get($columns, 'academic_year_id'), fn ($q, $v) => $q->where('academic_year_id', $v))
            ->when(Arr::get($columns, 'course_id'), fn ($q, $v) => $q->where('course_id', $v))
            ->when(Arr::get($columns, 'specialty_id'), fn ($q, $v) => $q->where('specialty_id', $v))
            ->when(Arr::get($columns, 'parallel_id'), fn ($q, $v) => $q->where('parallel_id', $v))
            ->when(Arr::get($columns, 'modality_id'), fn ($q, $v) => $q->where('modality_id', $v))
            ->when(Arr::get($columns, 'shift_id'), fn ($q, $v) => $q->where('shift_id', $v))
            ->when(Arr::get($columns, 'status'), fn ($q, $v) => $q->where('status', $v))

            ->orderBy($sort, $dir)
            ->paginate($perPage);
    }

    public function active(array $filters = []): LengthAwarePaginator
    {
        $tenantId = $this->requireTenantId();

        $perPage = max(1, min((int) Arr::get($filters, 'per_page', 15), 100));

        $rawQ = Arr::get($filters, 'q', '');
        $decodedQ = [];

        if (is_string($rawQ) && trim($rawQ) !== '') {
            $decoded = json_decode($rawQ, true);
            $decodedQ = is_array($decoded) ? $decoded : [];
        } elseif (is_array($rawQ)) {
            $decodedQ = $rawQ;
        }

        $columns = Arr::get($decodedQ, 'columns', []);

        return AcademicSchedule::query()
            ->with($this->relations())
            ->where('tenant_id', $tenantId)
            ->whereIn('status', ['draft', 'in_progress', 'accepted'])
            ->when(Arr::get($columns, 'academic_year_id'), fn ($q, $v) => $q->where('academic_year_id', $v))
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    public function find(AcademicSchedule $academicSchedule): AcademicSchedule
    {
        $tenantId = $this->requireTenantId();

        if ((string) $academicSchedule->tenant_id !== $tenantId) {
            abort(404);
        }

        return $academicSchedule->load($this->relations());
    }

    public function create(array $data): AcademicSchedule
    {
        $tenantId = $this->requireTenantId();

        $academicSchedule = DB::transaction(function () use ($data, $tenantId) {
            $checkConflicts = (bool) Arr::get($data, 'check_conflicts', false);

            if ($checkConflicts) {
                $this->ensureNoConflicts($data, $tenantId);
            }

            $this->ensureScheduleDoesNotExist($data, $tenantId);

            $academicSchedule = AcademicSchedule::query()->create([
                'tenant_id' => $tenantId,
                ...$this->extractSchedulePayload($data),
            ]);

            $this->resetFrequenciesOnly($academicSchedule, Arr::get($data, 'frequencies', []));

            $this->markCalendarSyncAsPending($academicSchedule);

            return $academicSchedule->refresh()->load($this->relations());
        });

        return $academicSchedule;
    }

    public function update(AcademicSchedule $academicSchedule, array $data): AcademicSchedule
    {
        $tenantId = $this->requireTenantId();

        if ((string) $academicSchedule->tenant_id !== $tenantId) {
            abort(404);
        }

        $academicSchedule = DB::transaction(function () use ($academicSchedule, $data, $tenantId) {
            $payloadForValidation = [
                ...$academicSchedule->only([
                    'academic_year_id',
                    'course_id',
                    'specialty_id',
                    'parallel_id',
                    'modality_id',
                    'shift_id',
                    'status',
                    'general_observation',
                ]),
                ...Arr::only($data, [
                    'academic_year_id',
                    'course_id',
                    'specialty_id',
                    'parallel_id',
                    'modality_id',
                    'shift_id',
                    'status',
                    'general_observation',
                ]),
                'frequencies' => Arr::get($data, 'frequencies', $academicSchedule->frequencies->toArray()),
            ];

            $checkConflicts = (bool) Arr::get($data, 'check_conflicts', false);

            if ($checkConflicts) {
                $this->ensureNoConflicts($payloadForValidation, $tenantId, $academicSchedule->id);
            }

            $this->ensureScheduleDoesNotExistForUpdate($academicSchedule, $payloadForValidation, $tenantId);

            $academicSchedule->fill($this->extractSchedulePayload($data));
            $academicSchedule->save();

            if (array_key_exists('frequencies', $data)) {
                $this->resetFrequenciesOnly(
                    $academicSchedule,
                    Arr::get($data, 'frequencies', [])
                );
            }

            $this->markCalendarSyncAsPending($academicSchedule);

            return $academicSchedule->refresh()->load($this->relations());
        });

        return $academicSchedule;
    }

    public function delete(AcademicSchedule $academicSchedule): void
    {
        $tenantId = $this->requireTenantId();

        if ((string) $academicSchedule->tenant_id !== $tenantId) {
            abort(404);
        }

        DB::transaction(function () use ($academicSchedule) {
            $this->deleteCalendarEventsForSchedule($academicSchedule);
            $academicSchedule->frequencies()->delete();
            $academicSchedule->delete();
        });
    }

    protected function resetFrequenciesOnly(AcademicSchedule $academicSchedule, array $frequencies): void {
        $academicSchedule->frequencies()->delete();

        foreach ($frequencies as $frequencyData) {
            $academicSchedule->frequencies()->create(
                $this->extractFrequencyPayload($frequencyData)
            );
        }
    }

    protected function markCalendarSyncAsPending(AcademicSchedule $academicSchedule): void
    {
        $academicSchedule->forceFill([
            'calendar_sync_status' => 'pending',
            'calendar_sync_error' => null,
            'calendar_sync_requested_at' => null,
            'calendar_synced_at' => null,
            'calendar_sync_total_events' => 0,
            'calendar_sync_processed_events' => 0,
            'calendar_sync_progress' => 0,
        ])->save();
    }

    protected function deleteCalendarEventsForSchedule(AcademicSchedule $academicSchedule): void
    {
        $eventIds = CalendarEvent::query()
            ->where('tenant_id', $academicSchedule->tenant_id)
            ->where('source', 'academic_schedule')
            ->where('metadata->academic_schedule_id', (string) $academicSchedule->id)
            ->pluck('id');

        if ($eventIds->isEmpty()) {
            return;
        }

        DB::table('calendar_event_participants')
            ->whereIn('calendar_event_id', $eventIds)
            ->delete();

        DB::table('calendar_event_audiences')
            ->whereIn('calendar_event_id', $eventIds)
            ->delete();

        CalendarEvent::query()
            ->whereIn('id', $eventIds)
            ->delete();
    }

    /**
     * @throws ValidationException
     */
    protected function createCalendarEventsForFrequency(
        AcademicSchedule $academicSchedule,
        AcademicScheduleFrequency $frequency
    ): ?CalendarEvent {
        $academicSchedule->loadMissing([
            'academicYear',
            'course',
            'course.educationalLevel',
            'specialty',
            'parallel',
            'shift',
            'modality',
        ]);

        $frequency->loadMissing([
            'classroom',
            'subject',
            'instructor.person.user',
        ]);

        $startDate = CarbonImmutable::parse($academicSchedule->academicYear->start_date)->startOfDay();
        $endDate = CarbonImmutable::parse($academicSchedule->academicYear->end_date)->endOfDay();

        $firstDate = $this->firstDateForDayOfWeek(
            $startDate,
            (int) $frequency->day_of_week
        );

        $startTime = CarbonImmutable::parse($frequency->start_time)->format('H:i:s');
        $endTime = CarbonImmutable::parse($frequency->end_time)->format('H:i:s');

        $title = trim(sprintf(
            '%s - %s %s',
            $frequency->subject?->name ?? 'Class',
            $academicSchedule->course?->name ?? '',
            $academicSchedule->parallel?->name
                ? '(' . $academicSchedule->parallel->name . ')'
                : ''
        ));

        $creatorId = $frequency->instructor?->person?->user?->id;

        if (! $creatorId) {
            throw ValidationException::withMessages([
                'frequencies' => __('messages.academic_schedules.instructor_user_required'),
            ]);
        }

        $eventType = CalendarEventType::query()
            ->where('tenant_id', $academicSchedule->tenant_id)
            ->where('code', 'class_shift')
            ->where('is_active', true)
            ->first();

        $firstCalendarEvent = null;
        $currentDate = $firstDate;

        while ($currentDate->lte($endDate)) {
            $startAt = CarbonImmutable::parse(
                $currentDate->format('Y-m-d') . ' ' . $startTime
            );

            $endAt = CarbonImmutable::parse(
                $currentDate->format('Y-m-d') . ' ' . $endTime
            );

            $calendarEvent = CalendarEvent::query()->create([
                'tenant_id' => $academicSchedule->tenant_id,
                'event_type_id' => $eventType?->id,

                'created_by' => $creatorId,
                'creator_id' => $creatorId,

                'title' => $title,
                'description' => $this->buildCalendarDescription($academicSchedule, $frequency),
                'location' => $frequency->classroom?->name,

                'start_at' => $startAt,
                'end_at' => $endAt,

                'all_day' => false,
                'timezone' => 'UTC',
                'status' => 'confirmed',
                'visibility' => 'restricted',
                'source' => 'academic_schedule',
                'editable_by' => 'creator_only',

                'is_recurring' => false,
                'recurrence_rule' => null,

                'google_sync_enabled' => false,

                'metadata' => [
                    'academic_schedule_id' => (string) $academicSchedule->id,
                    'academic_schedule_frequency_id' => (string) $frequency->id,
                    'academic_year_id' => (string) $academicSchedule->academic_year_id,
                    'course_id' => (string) $academicSchedule->course_id,
                    'specialty_id' => $academicSchedule->specialty_id
                        ? (string) $academicSchedule->specialty_id
                        : null,
                    'parallel_id' => (string) $academicSchedule->parallel_id,
                    'shift_id' => (string) $academicSchedule->shift_id,
                    'modality_id' => (string) $academicSchedule->modality_id,
                    'classroom_id' => (string) $frequency->classroom_id,
                    'subject_id' => (string) $frequency->subject_id,
                    'instructor_id' => (string) $frequency->instructor_id,
                    'day_of_week' => (int) $frequency->day_of_week,
                    'generated_from_schedule_job' => true,
                ],
            ]);

            $this->syncCalendarParticipants(
                $calendarEvent,
                $academicSchedule,
                $frequency
            );

            $this->incrementCalendarSyncProgress($academicSchedule);

            if (! $firstCalendarEvent) {
                $firstCalendarEvent = $calendarEvent;
            }

            $currentDate = $currentDate->addWeek();
        }

        return $firstCalendarEvent;
    }

    protected function syncCalendarParticipants(CalendarEvent $calendarEvent, AcademicSchedule $academicSchedule, AcademicScheduleFrequency $frequency): void {
        $enrollments = Enrollment::query()
            ->with('student.person.user')
            ->where('tenant_id', $academicSchedule->tenant_id)
            ->where('academic_year_id', $academicSchedule->academic_year_id)
            ->where('course_id', $academicSchedule->course_id)
            ->when(
                $academicSchedule->specialty_id,
                fn ($query) => $query->where('specialty_id', $academicSchedule->specialty_id),
                fn ($query) => $query->whereNull('specialty_id')
            )
            ->where('parallel_id', $academicSchedule->parallel_id)
            ->where('modality_id', $academicSchedule->modality_id)
            ->where('shift_id', $academicSchedule->shift_id)
            ->where('is_active', true)
            ->get();

        $rows = [];

        foreach ($enrollments as $enrollment) {
            $person = $enrollment->student?->person;

            if (! $person) {
                continue;
            }

            $rows[] = [
                'id' => (string) Str::uuid(),
                'tenant_id' => $academicSchedule->tenant_id,
                'calendar_event_id' => $calendarEvent->id,
                'user_id' => $person->user?->id,
                'person_id' => $person->id,
                'participant_type' => 'student',
                'role' => 'attendee',
                'response_status' => 'accepted',
                'is_required' => true,
                'can_view' => true,
                'can_receive_notifications' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if ($frequency->instructor?->person) {
            $rows[] = [
                'id' => (string) Str::uuid(),
                'tenant_id' => $academicSchedule->tenant_id,
                'calendar_event_id' => $calendarEvent->id,
                'user_id' => $frequency->instructor->person->user?->id,
                'person_id' => $frequency->instructor->person->id,
                'participant_type' => 'teacher',
                'role' => 'organizer',
                'response_status' => 'accepted',
                'is_required' => true,
                'can_view' => true,
                'can_receive_notifications' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if (! empty($rows)) {
            DB::table('calendar_event_participants')->insert($rows);
        }
    }

    /**
     * @throws ValidationException
     */
    protected function ensureNoConflicts(array $data, string $tenantId, ?string $ignoreScheduleId = null): void {
        $frequencies = Arr::get($data, 'frequencies', []);

        foreach ($frequencies as $index => $frequency) {
            $this->ensureFrequencyHasValidTime($frequency, "frequencies.{$index}.end_time");

            $conflict = AcademicScheduleFrequency::query()
                ->whereHas('academicSchedule', function ($query) use ($data, $tenantId, $ignoreScheduleId) {
                    $query->where('tenant_id', $tenantId)
                        ->where('academic_year_id', Arr::get($data, 'academic_year_id'));

                    if ($ignoreScheduleId) {
                        $query->where('id', '!=', $ignoreScheduleId);
                    }
                })
                ->where('day_of_week', Arr::get($frequency, 'day_of_week'))
                ->where(function ($query) use ($frequency) {
                    $query->where('classroom_id', Arr::get($frequency, 'classroom_id'))
                        ->orWhere('instructor_id', Arr::get($frequency, 'instructor_id'));
                })
                ->where('start_time', '<', Arr::get($frequency, 'end_time'))
                ->where('end_time', '>', Arr::get($frequency, 'start_time'))
                ->exists();

            if ($conflict) {
                throw ValidationException::withMessages([
                    "frequencies.{$index}.start_time" => __('messages.academic_schedules.conflict_detected'),
                ]);
            }
        }
    }

    /**
     * @throws ValidationException
     */
    protected function ensureFrequencyHasValidTime(array $frequency, string $field): void
    {
        $start = Arr::get($frequency, 'start_time');
        $end = Arr::get($frequency, 'end_time');

        if ($start && $end && $end <= $start) {
            throw ValidationException::withMessages([
                $field => __('messages.academic_schedules.end_time_must_be_after_start_time'),
            ]);
        }
    }

    /**
     * @throws ValidationException
     */
    protected function ensureScheduleDoesNotExist(array $data, string $tenantId): void
    {
        $exists = AcademicSchedule::query()
            ->where('tenant_id', $tenantId)
            ->where('academic_year_id', Arr::get($data, 'academic_year_id'))
            ->where('course_id', Arr::get($data, 'course_id'))
            ->where('specialty_id', Arr::get($data, 'specialty_id'))
            ->where('parallel_id', Arr::get($data, 'parallel_id'))
            ->where('modality_id', Arr::get($data, 'modality_id'))
            ->where('shift_id', Arr::get($data, 'shift_id'))
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'course_id' => __('messages.academic_schedules.already_exists'),
            ]);
        }
    }

    /**
     * @throws ValidationException
     */
    protected function ensureScheduleDoesNotExistForUpdate(AcademicSchedule $academicSchedule, array $data, string $tenantId): void {
        $exists = AcademicSchedule::query()
            ->where('tenant_id', $tenantId)
            ->where('id', '!=', $academicSchedule->id)
            ->where('academic_year_id', Arr::get($data, 'academic_year_id'))
            ->where('course_id', Arr::get($data, 'course_id'))
            ->where('specialty_id', Arr::get($data, 'specialty_id'))
            ->where('parallel_id', Arr::get($data, 'parallel_id'))
            ->where('modality_id', Arr::get($data, 'modality_id'))
            ->where('shift_id', Arr::get($data, 'shift_id'))
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'course_id' => __('messages.academic_schedules.already_exists'),
            ]);
        }
    }

    protected function extractSchedulePayload(array $data): array
    {
        return Arr::only($data, [
            'academic_year_id',
            'course_id',
            'specialty_id',
            'parallel_id',
            'modality_id',
            'shift_id',
            'status',
            'general_observation',
        ]);
    }

    protected function extractFrequencyPayload(array $data): array
    {
        return Arr::only($data, [
            'day_of_week',
            'start_time',
            'end_time',
            'classroom_id',
            'subject_id',
            'instructor_id',
            'observation',
        ]);
    }

    protected function relations(): array
    {
        return [
            'tenant:id,name',
            'academicYear:id,name,start_date,end_date,is_active,is_current',
            'course:id,tenant_id,educational_level_id,code,name,level_number,status',
            'course.educationalLevel:id,tenant_id,code,name,has_specialty',
            'specialty:id,tenant_id,code,name,is_active',
            'parallel:id,tenant_id,code,name,is_active',
            'modality:id,tenant_id,code,name,is_active',
            'shift:id,tenant_id,code,name,is_active',

            'frequencies:id,academic_schedule_id,day_of_week,start_time,end_time,classroom_id,subject_id,instructor_id,calendar_event_id,observation',
            'frequencies.classroom:id,tenant_id,code,name,capacity,location,is_active',
            'frequencies.subject:id,tenant_id,code,name,is_active',
            'frequencies.instructor:id,person_id,department_id,academic_title,academic_level,status',
            'frequencies.instructor.person:id,full_name,email,phone,photo',
            'frequencies.calendarEvent:id,title,start_at,end_at,status,source',
        ];
    }

    /**
     * @throws ValidationException
     */
    protected function requireTenantId(): string
    {
        $tenantId = $this->resolveCurrentTenantId();

        if (! $tenantId) {
            throw ValidationException::withMessages([
                'tenant' => __('messages.academic_schedules.tenant_not_resolved'),
            ]);
        }

        return $tenantId;
    }

    protected function firstDateForDayOfWeek(CarbonImmutable $startDate, int $dayOfWeek): CarbonImmutable
    {
        $current = $startDate;

        while ((int) $current->isoWeekday() !== $dayOfWeek) {
            $current = $current->addDay();
        }

        return $current;
    }

    protected function buildRecurrenceRule(int $dayOfWeek, CarbonImmutable $endDate): string
    {
        $days = [
            1 => 'MO',
            2 => 'TU',
            3 => 'WE',
            4 => 'TH',
            5 => 'FR',
            6 => 'SA',
            7 => 'SU',
        ];

        $until = $endDate->endOfDay()->utc()->format('Ymd\THis\Z');

        return sprintf(
            'RRULE:FREQ=WEEKLY;BYDAY=%s;UNTIL=%s',
            $days[$dayOfWeek] ?? 'MO',
            $until
        );
    }

    protected function buildCalendarDescription(AcademicSchedule $academicSchedule, AcademicScheduleFrequency $frequency): string {
        return trim(implode(PHP_EOL, array_filter([
            'Academic schedule class',
            'Course: ' . ($academicSchedule->course?->name ?? ''),
            'Specialty: ' . ($academicSchedule->specialty?->name ?? ''),
            'Parallel: ' . ($academicSchedule->parallel?->name ?? ''),
            'Shift: ' . ($academicSchedule->shift?->name ?? ''),
            'Modality: ' . ($academicSchedule->modality?->name ?? ''),
            'Subject: ' . ($frequency->subject?->name ?? ''),
            'Instructor: ' . ($frequency->instructor?->person?->full_name ?? ''),
            $academicSchedule->general_observation,
            $frequency->observation,
        ])));
    }

    public function markCalendarSyncAsProcessing(AcademicSchedule $academicSchedule): AcademicSchedule
    {
        $tenantId = $this->requireTenantId();

        if ((string) $academicSchedule->tenant_id !== $tenantId) {
            abort(404);
        }

        if ($academicSchedule->calendar_sync_status === 'processing') {
            throw ValidationException::withMessages([
                'calendar_sync_status' => __('messages.academic_schedules.calendar_generation_already_processing'),
            ]);
        }

        $academicSchedule->forceFill([
            'calendar_sync_status' => 'processing',
            'calendar_sync_error' => null,
            'calendar_sync_requested_at' => now(),
            'calendar_synced_at' => null,
            'calendar_sync_total_events' => 0,
            'calendar_sync_processed_events' => 0,
            'calendar_sync_progress' => 0,
        ])->save();

        return $academicSchedule->refresh()->load($this->relations());
    }

    public function generateCalendarEventsForSchedule(AcademicSchedule $academicSchedule): AcademicSchedule
    {
        $academicSchedule->loadMissing([
            'academicYear',
            'course',
            'course.educationalLevel',
            'specialty',
            'parallel',
            'shift',
            'modality',
            'frequencies',
        ]);

        $totalEvents = $this->calculateTotalCalendarEventsForSchedule($academicSchedule);

        $academicSchedule->forceFill([
            'calendar_sync_status' => 'processing',
            'calendar_sync_error' => null,
            'calendar_sync_total_events' => $totalEvents,
            'calendar_sync_processed_events' => 0,
            'calendar_sync_progress' => 0,
            'calendar_synced_at' => null,
        ])->save();

        $this->deleteCalendarEventsForSchedule($academicSchedule);

        foreach ($academicSchedule->frequencies as $frequency) {
            $firstCalendarEvent = $this->createCalendarEventsForFrequency(
                $academicSchedule,
                $frequency
            );

            if ($firstCalendarEvent) {
                $frequency->forceFill([
                    'calendar_event_id' => $firstCalendarEvent->id,
                ])->save();
            }
        }

        $academicSchedule->forceFill([
            'calendar_sync_status' => 'completed',
            'calendar_sync_processed_events' => $academicSchedule->calendar_sync_total_events,
            'calendar_sync_progress' => 100,
            'calendar_synced_at' => now(),
        ])->save();

        return $academicSchedule->refresh()->load($this->relations());
    }

    protected function calculateTotalCalendarEventsForSchedule(AcademicSchedule $academicSchedule): int
    {
        $academicSchedule->loadMissing([
            'academicYear',
            'frequencies',
        ]);

        $startDate = CarbonImmutable::parse($academicSchedule->academicYear->start_date)->startOfDay();
        $endDate = CarbonImmutable::parse($academicSchedule->academicYear->end_date)->endOfDay();

        $total = 0;

        foreach ($academicSchedule->frequencies as $frequency) {
            $firstDate = $this->firstDateForDayOfWeek(
                $startDate,
                (int) $frequency->day_of_week
            );

            $currentDate = $firstDate;

            while ($currentDate->lte($endDate)) {
                $total++;
                $currentDate = $currentDate->addWeek();
            }
        }

        return $total;
    }

    protected function incrementCalendarSyncProgress(AcademicSchedule $academicSchedule): void
    {
        $processed = ((int) $academicSchedule->calendar_sync_processed_events) + 1;

        $academicSchedule->calendar_sync_processed_events = $processed;

        if ($processed % 10 !== 0) {
            return;
        }

        $total = max(
            (int) $academicSchedule->calendar_sync_total_events,
            1
        );

        $progress = (int) floor(($processed / $total) * 100);

        $academicSchedule->forceFill([
            'calendar_sync_processed_events' => $processed,
            'calendar_sync_progress' => min($progress, 99),
        ])->save();
    }

    public function ensureInstructorsHaveUsers(AcademicSchedule $academicSchedule): void
    {
        $academicSchedule->loadMissing([
            'frequencies.instructor.person.user',
        ]);

        $missing = $academicSchedule->frequencies
            ->filter(function ($frequency) {
                return ! $frequency->instructor?->person?->user;
            })
            ->map(function ($frequency) {
                return $frequency->instructor?->person?->full_name
                    ?? __('messages.academic_schedules.unknown_instructor');
            })
            ->unique()
            ->values();

        if ($missing->isNotEmpty()) {
            throw ValidationException::withMessages([
                'instructors' => [
                    __('messages.academic_schedules.instructors_without_user') . ': '
                    . $missing->implode(', '),
                ],
            ]);
        }
    }

    public function calendarSyncStatus(AcademicSchedule $academicSchedule): array
    {
        $tenantId = $this->requireTenantId();

        if ((string) $academicSchedule->tenant_id !== $tenantId) {
            abort(404);
        }

        $academicSchedule->refresh();

        return [
            'id' => (string) $academicSchedule->id,
            'calendar_sync_status' => $academicSchedule->calendar_sync_status,
            'calendar_sync_error' => $academicSchedule->calendar_sync_error,
            'calendar_sync_requested_at' => optional($academicSchedule->calendar_sync_requested_at)?->toIso8601String(),
            'calendar_synced_at' => optional($academicSchedule->calendar_synced_at)?->toIso8601String(),
            'calendar_sync_total_events' => (int) $academicSchedule->calendar_sync_total_events,
            'calendar_sync_processed_events' => (int) $academicSchedule->calendar_sync_processed_events,
            'calendar_sync_progress' => (int) $academicSchedule->calendar_sync_progress,
        ];
    }

}
