<?php
//
//namespace App\Console\Commands;
//
//use App\Models\Academic\AcademicSchedule;
//use App\Models\Academic\AcademicScheduleFrequency;
//use App\Models\Academic\AcademicYear;
//use App\Models\Academic\Instructor;
//use App\Models\Administration\User;
//use App\Models\Calendar\CalendarEvent;
//use App\Models\Calendar\CalendarEventType;
//use App\Models\MigrationIdMap;
//use Carbon\CarbonImmutable;
//use Illuminate\Console\Command;
//use Illuminate\Support\Facades\DB;
//
//class MigrateV1AcademicSchedules extends Command
//{
//    protected $signature = 'migrate:v1-academic-schedules {--fresh : Delete migrated schedules, frequencies and calendar events}';
//
//    protected $description = 'Migrate v1 schedules and schedule patterns to v2 academic schedules, frequencies and calendar events';
//
//    public function handle(): int
//    {
//        $this->info('Starting academic schedules migration from v1...');
//
//        if ($this->option('fresh')) {
//            $this->fresh();
//        }
//
//        $createdSchedules = 0;
//        $createdFrequencies = 0;
//        $createdEvents = 0;
//        $skipped = 0;
//        $failed = 0;
//
//        $rows = DB::connection('pgsql_v1')
//            ->table('ac.schedules')
//            ->orderBy('created_at')
//            ->get();
//
//        foreach ($rows as $oldSchedule) {
//            DB::beginTransaction();
//
//            try {
//                if ($this->mappedId('academic_schedule', $oldSchedule->id)) {
//                    $skipped++;
//                    DB::commit();
//                    continue;
//                }
//
//                $tenantId = $this->mappedId('tenant', $oldSchedule->company_id);
//                $academicYearId = $this->mappedId('academic_year', $oldSchedule->academic_year_id);
//                $courseId = $this->mappedId('course', $oldSchedule->course_id);
//                $specialtyId = $oldSchedule->specialty_id
//                    ? $this->mappedId('specialty', $oldSchedule->specialty_id)
//                    : null;
//                $parallelId = $this->mappedId('parallel', $oldSchedule->parallel_id);
//                $modalityId = $this->mappedId('modality', $oldSchedule->modality_id);
//
//                // IMPORTANTE:
//                // v1 section_id corresponde a v2 shift_id
//                $shiftId = $this->mappedId('shift', $oldSchedule->section_id);
//
//                if (! $tenantId || ! $academicYearId || ! $courseId || ! $parallelId || ! $modalityId || ! $shiftId) {
//                    $this->warn("Missing mapped ids for v1 schedule {$oldSchedule->id}");
//                    $failed++;
//                    DB::rollBack();
//                    continue;
//                }
//
//                $academicSchedule = AcademicSchedule::query()->create([
//                    'tenant_id' => $tenantId,
//                    'academic_year_id' => $academicYearId,
//                    'course_id' => $courseId,
//                    'specialty_id' => $specialtyId,
//                    'parallel_id' => $parallelId,
//                    'modality_id' => $modalityId,
//                    'shift_id' => $shiftId,
//                    'status' => $oldSchedule->is_active ? 'accepted' : 'draft',
//                    'general_observation' => $oldSchedule->observation,
//                    'created_at' => $oldSchedule->created_at ?? now(),
//                    'updated_at' => $oldSchedule->updated_at ?? now(),
//                ]);
//
//                $this->createMap('academic_schedule', $oldSchedule->id, $academicSchedule->id, [
//                    'old_company_id' => $oldSchedule->company_id,
//                    'old_section_id' => $oldSchedule->section_id,
//                    'mapped_section_id_as_shift_id' => true,
//                ]);
//
//                $createdSchedules++;
//
//                $patterns = DB::connection('pgsql_v1')
//                    ->table('ac.schedule_patterns')
//                    ->where('schedule_id', $oldSchedule->id)
//                    ->where('is_active', true)
//                    ->orderBy('weekday')
//                    ->orderBy('start_time')
//                    ->get();
//
//                foreach ($patterns as $oldPattern) {
//                    $classroomId = $this->mappedId('classroom', $oldPattern->facility_id);
//                    $subjectId = $this->mappedId('subject', $oldPattern->subject_id);
//                    $instructorId = $this->mappedId('instructor', $oldPattern->instructor_id);
//
//                    if (! $classroomId || ! $subjectId || ! $instructorId) {
//                        $this->warn("Missing mapped ids for v1 schedule pattern {$oldPattern->id}");
//                        $failed++;
//                        continue;
//                    }
//
//                    $dayOfWeek = $this->normalizeDayOfWeek($oldPattern->weekday);
//
//                    if (! $dayOfWeek) {
//                        $this->warn("Invalid weekday '{$oldPattern->weekday}' for pattern {$oldPattern->id}");
//                        $failed++;
//                        continue;
//                    }
//
//                    $frequency = AcademicScheduleFrequency::query()->create([
//                        'academic_schedule_id' => $academicSchedule->id,
//                        'day_of_week' => $dayOfWeek,
//                        'start_time' => $this->secondsToTime($oldPattern->start_time),
//                        'end_time' => $this->secondsToTime($oldPattern->end_time),
//                        'classroom_id' => $classroomId,
//                        'subject_id' => $subjectId,
//                        'instructor_id' => $instructorId,
//                        'observation' => null,
//                        'created_at' => $oldPattern->created_at ?? now(),
//                        'updated_at' => $oldPattern->updated_at ?? now(),
//                    ]);
//
//                    $this->createMap('academic_schedule_frequency', $oldPattern->id, $frequency->id, [
//                        'old_schedule_id' => $oldPattern->schedule_id,
//                        'source' => 'schedule_pattern',
//                    ]);
//
//                    $createdFrequencies++;
//
//                    $firstEvent = $this->createCalendarEventsForFrequency(
//                        $academicSchedule,
//                        $frequency
//                    );
//
//                    if ($firstEvent) {
//                        $frequency->forceFill([
//                            'calendar_event_id' => $firstEvent->id,
//                        ])->save();
//                    }
//
//                    $createdEvents += CalendarEvent::query()
//                        ->where('tenant_id', $academicSchedule->tenant_id)
//                        ->where('source', 'academic_schedule')
//                        ->where('metadata->academic_schedule_frequency_id', (string) $frequency->id)
//                        ->count();
//                }
//
//                DB::commit();
//            } catch (\Throwable $e) {
//                DB::rollBack();
//
//                $failed++;
//                $this->error("Schedule {$oldSchedule->id}: {$e->getMessage()}");
//            }
//        }
//
//        $this->table(
//            ['Schedules', 'Frequencies', 'Events', 'Skipped', 'Failed'],
//            [[$createdSchedules, $createdFrequencies, $createdEvents, $skipped, $failed]]
//        );
//
//        return $failed > 0 ? self::FAILURE : self::SUCCESS;
//    }
//
//    private function fresh(): void
//    {
//        $this->warn('Fresh enabled. Deleting migrated academic schedule data...');
//
//        $scheduleIds = MigrationIdMap::query()
//            ->where('entity', 'academic_schedule')
//            ->pluck('new_id');
//
//        CalendarEvent::query()
//            ->where('source', 'academic_schedule')
//            ->whereIn('metadata->academic_schedule_id', $scheduleIds)
//            ->forceDelete();
//
//        AcademicScheduleFrequency::query()
//            ->whereIn('academic_schedule_id', $scheduleIds)
//            ->forceDelete();
//
//        AcademicSchedule::query()
//            ->whereIn('id', $scheduleIds)
//            ->forceDelete();
//
//        MigrationIdMap::query()
//            ->whereIn('entity', [
//                'academic_schedule',
//                'academic_schedule_frequency',
//                'calendar_event',
//            ])
//            ->delete();
//    }
//
//    private function createCalendarEventsForFrequency(
//        AcademicSchedule $schedule,
//        AcademicScheduleFrequency $frequency
//    ): ?CalendarEvent {
//        $schedule->loadMissing([
//            'academicYear',
//            'course',
//            'specialty',
//            'parallel',
//            'shift',
//            'modality',
//        ]);
//
//        $frequency->loadMissing([
//            'classroom',
//            'subject',
//            'instructor.person',
//        ]);
//
//        $academicYear = AcademicYear::query()->find($schedule->academic_year_id);
//
//        if (! $academicYear || ! $academicYear->start_date || ! $academicYear->end_date) {
//            $this->warn("Academic year dates not found for schedule {$schedule->id}");
//            return null;
//        }
//
//        $startDate = CarbonImmutable::parse($academicYear->start_date)->startOfDay();
//        $endDate = CarbonImmutable::parse($academicYear->end_date)->endOfDay();
//
//        $currentDate = $this->firstDateForDayOfWeek(
//            $startDate,
//            (int) $frequency->day_of_week
//        );
//
//        $startTime = CarbonImmutable::parse($frequency->start_time)->format('H:i:s');
//        $endTime = CarbonImmutable::parse($frequency->end_time)->format('H:i:s');
//
//        $creatorId = $this->resolveCreatorId($frequency->instructor_id);
//
//        if (! $creatorId) {
//            $this->warn("Creator user not found for instructor {$frequency->instructor_id}");
//            return null;
//        }
//
//        $eventTypeId = CalendarEventType::query()
//            ->where('tenant_id', $schedule->tenant_id)
//            ->where('code', 'class_shift')
//            ->where('is_active', true)
//            ->value('id');
//
//        $firstEvent = null;
//
//        while ($currentDate->lte($endDate)) {
//            $startAt = CarbonImmutable::parse($currentDate->format('Y-m-d') . ' ' . $startTime);
//            $endAt = CarbonImmutable::parse($currentDate->format('Y-m-d') . ' ' . $endTime);
//
//            $event = CalendarEvent::query()->create([
//                'tenant_id' => $schedule->tenant_id,
//                'event_type_id' => $eventTypeId,
//                'created_by' => $creatorId,
//                'updated_by' => $creatorId,
//
//                'title' => trim(sprintf(
//                    '%s - %s %s',
//                    $frequency->subject?->name ?? 'Class',
//                    $schedule->course?->name ?? '',
//                    $schedule->parallel?->name ? '(' . $schedule->parallel->name . ')' : ''
//                )),
//
//                'description' => $this->buildCalendarDescription($schedule, $frequency),
//                'location' => $frequency->classroom?->name,
//
//                'start_at' => $startAt,
//                'end_at' => $endAt,
//                'all_day' => false,
//                'timezone' => config('app.timezone', 'America/New_York'),
//
//                'status' => 'confirmed',
//                'visibility' => 'private',
//                'source' => 'academic_schedule',
//                'editable_by' => 'creator_only',
//
//                'is_recurring' => false,
//                'recurrence_rule' => null,
//                'google_sync_enabled' => false,
//
//                'metadata' => [
//                    'academic_schedule_id' => (string) $schedule->id,
//                    'academic_schedule_frequency_id' => (string) $frequency->id,
//                    'academic_year_id' => (string) $schedule->academic_year_id,
//                    'course_id' => (string) $schedule->course_id,
//                    'specialty_id' => $schedule->specialty_id ? (string) $schedule->specialty_id : null,
//                    'parallel_id' => (string) $schedule->parallel_id,
//                    'shift_id' => (string) $schedule->shift_id,
//                    'modality_id' => (string) $schedule->modality_id,
//                    'classroom_id' => (string) $frequency->classroom_id,
//                    'subject_id' => (string) $frequency->subject_id,
//                    'instructor_id' => (string) $frequency->instructor_id,
//                    'day_of_week' => (int) $frequency->day_of_week,
//                ],
//
//                'created_at' => now(),
//                'updated_at' => now(),
//            ]);
//
//            $this->createMap('calendar_event', $event->id, $event->id, [
//                'academic_schedule_id' => (string) $schedule->id,
//                'academic_schedule_frequency_id' => (string) $frequency->id,
//                'generated_from_migration' => true,
//            ]);
//
//            if (! $firstEvent) {
//                $firstEvent = $event;
//            }
//
//            $currentDate = $currentDate->addWeek();
//        }
//
//        return $firstEvent;
//    }
//
//    private function firstDateForDayOfWeek(CarbonImmutable $startDate, int $dayOfWeek): CarbonImmutable
//    {
//        $current = $startDate;
//
//        while ((int) $current->isoWeekday() !== $dayOfWeek) {
//            $current = $current->addDay();
//        }
//
//        return $current;
//    }
//
//    private function secondsToTime(?int $seconds): string
//    {
//        $seconds = (int) ($seconds ?? 0);
//
//        $hours = floor($seconds / 3600);
//        $minutes = floor(($seconds % 3600) / 60);
//
//        return sprintf('%02d:%02d:00', $hours, $minutes);
//    }
//
//    private function normalizeDayOfWeek($value): ?int
//    {
//        $value = trim((string) $value);
//
//        if (is_numeric($value)) {
//            $day = (int) $value;
//
//            return $day >= 1 && $day <= 7 ? $day : null;
//        }
//
//        $normalized = str($value)->ascii()->lower()->trim()->toString();
//
//        return match ($normalized) {
//            'monday', 'lunes', 'mon', 'lun' => 1,
//            'tuesday', 'martes', 'tue', 'mar' => 2,
//            'wednesday', 'miercoles', 'miércoles', 'wed', 'mie', 'mié' => 3,
//            'thursday', 'jueves', 'thu', 'jue' => 4,
//            'friday', 'viernes', 'fri', 'vie' => 5,
//            'saturday', 'sabado', 'sábado', 'sat', 'sab', 'sáb' => 6,
//            'sunday', 'domingo', 'sun', 'dom' => 7,
//            default => null,
//        };
//    }
//
//    private function resolveCreatorId(string $instructorId): ?string
//    {
//        $instructor = Instructor::query()
//            ->with('person')
//            ->find($instructorId);
//
//        if ($instructor?->person_id) {
//            $userId = User::query()
//                ->where('person_id', $instructor->person_id)
//                ->value('id');
//
//            if ($userId) {
//                return $userId;
//            }
//        }
//
//        return User::query()->value('id');
//    }
//
//    private function buildCalendarDescription(
//        AcademicSchedule $schedule,
//        AcademicScheduleFrequency $frequency
//    ): string {
//        return trim(implode(PHP_EOL, array_filter([
//            'Academic schedule class',
//            'Course: ' . ($schedule->course?->name ?? ''),
//            'Specialty: ' . ($schedule->specialty?->name ?? ''),
//            'Parallel: ' . ($schedule->parallel?->name ?? ''),
//            'Shift: ' . ($schedule->shift?->name ?? ''),
//            'Modality: ' . ($schedule->modality?->name ?? ''),
//            'Subject: ' . ($frequency->subject?->name ?? ''),
//            'Instructor: ' . ($frequency->instructor?->person?->full_name ?? ''),
//            $schedule->general_observation,
//            $frequency->observation,
//        ])));
//    }
//
//    private function mappedId(string $entity, ?string $oldId): ?string
//    {
//        if (! $oldId) {
//            return null;
//        }
//
//        return MigrationIdMap::query()
//            ->where('entity', $entity)
//            ->where('old_id', $oldId)
//            ->value('new_id');
//    }
//
//    private function createMap(string $entity, string $oldId, string $newId, array $metadata = []): void
//    {
//        MigrationIdMap::query()->updateOrCreate(
//            [
//                'entity' => $entity,
//                'old_id' => $oldId,
//            ],
//            [
//                'new_id' => $newId,
//                'metadata' => $metadata,
//            ]
//        );
//    }
//}

// esta migracion solo migra los eventos de calendario de annos actuales, no del anterior


namespace App\Console\Commands;

use App\Models\Academic\AcademicSchedule;
use App\Models\Academic\AcademicScheduleFrequency;
use App\Models\Academic\AcademicYear;
use App\Models\Academic\Instructor;
use App\Models\Administration\User;
use App\Models\Calendar\CalendarEvent;
use App\Models\Calendar\CalendarEventType;
use App\Models\MigrationIdMap;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MigrateV1AcademicSchedules extends Command
{
    protected $signature = 'migrate:v1-academic-schedules {--fresh : Delete migrated schedules, frequencies and academic schedule calendar events}';

    protected $description = 'Migrate v1 schedules and frequencies to v2 academic schedules. Events are generated only for academic years starting in 2026.';

    public function handle(): int
    {
        $this->info('Starting v1 academic schedules migration...');

        if ($this->option('fresh')) {
            $this->fresh();
        }

        $createdSchedules = 0;
        $createdFrequencies = 0;
        $createdEvents = 0;
        $skippedSchedules = 0;
        $failed = 0;

        $oldSchedules = DB::connection('pgsql_v1')
            ->table('ac.schedules')
            ->orderBy('created_at')
            ->get();

        foreach ($oldSchedules as $oldSchedule) {
            DB::beginTransaction();

            try {
                $existingScheduleId = $this->mappedId('academic_schedule', $oldSchedule->id);

                if ($existingScheduleId) {
                    $skippedSchedules++;
                    DB::commit();
                    continue;
                }

                $tenantId = $this->mappedId('tenant', $oldSchedule->company_id);
                $academicYearId = $this->mappedId('academic_year', $oldSchedule->academic_year_id);
                $courseId = $this->mappedId('course', $oldSchedule->course_id);
                $specialtyId = $oldSchedule->specialty_id
                    ? $this->mappedId('specialty', $oldSchedule->specialty_id)
                    : null;
                $parallelId = $this->mappedId('parallel', $oldSchedule->parallel_id);
                $modalityId = $this->mappedId('modality', $oldSchedule->modality_id);

                // En v1 section_id corresponde a shift_id en v2.
                $shiftId = $this->mappedId('shift', $oldSchedule->section_id);

                if (!$tenantId || !$academicYearId || !$courseId || !$parallelId || !$modalityId || !$shiftId) {
                    $this->warn("Missing mapped ids for v1 schedule {$oldSchedule->id}");
                    $failed++;
                    DB::rollBack();
                    continue;
                }

                $academicSchedule = AcademicSchedule::query()->create([
                    'tenant_id' => $tenantId,
                    'academic_year_id' => $academicYearId,
                    'course_id' => $courseId,
                    'specialty_id' => $specialtyId,
                    'parallel_id' => $parallelId,
                    'modality_id' => $modalityId,
                    'shift_id' => $shiftId,
                    'status' => $oldSchedule->is_active ? 'accepted' : 'draft',
                    'general_observation' => $oldSchedule->observation ?? null,
                    'created_at' => $oldSchedule->created_at ?? now(),
                    'updated_at' => $oldSchedule->updated_at ?? now(),
                ]);

                $this->createMap('academic_schedule', $oldSchedule->id, $academicSchedule->id, [
                    'old_company_id' => $oldSchedule->company_id,
                    'old_section_id' => $oldSchedule->section_id,
                    'mapped_section_id_as_shift_id' => true,
                ]);

                $createdSchedules++;

                $oldPatterns = DB::connection('pgsql_v1')
                    ->table('ac.schedule_patterns')
                    ->where('schedule_id', $oldSchedule->id)
                    ->orderBy('weekday')
                    ->orderBy('start_time')
                    ->get();

                foreach ($oldPatterns as $oldPattern) {
                    if (!$oldPattern->is_active) {
                        continue;
                    }

                    $classroomId = $this->mappedId('classroom', $oldPattern->facility_id);
                    $subjectId = $this->mappedId('subject', $oldPattern->subject_id);
                    $instructorId = $this->mappedId('instructor', $oldPattern->instructor_id);

                    if (!$classroomId || !$subjectId || !$instructorId) {
                        $this->warn("Missing mapped ids for v1 schedule pattern {$oldPattern->id}");
                        $failed++;
                        continue;
                    }

                    $dayOfWeek = $this->normalizeDayOfWeek($oldPattern->weekday);

                    if (!$dayOfWeek) {
                        $this->warn("Invalid weekday '{$oldPattern->weekday}' for v1 schedule pattern {$oldPattern->id}");
                        $failed++;
                        continue;
                    }

                    $frequency = AcademicScheduleFrequency::query()->create([
                        'academic_schedule_id' => $academicSchedule->id,
                        'day_of_week' => $dayOfWeek,
                        'start_time' => $this->secondsToTime($oldPattern->start_time),
                        'end_time' => $this->secondsToTime($oldPattern->end_time),
                        'classroom_id' => $classroomId,
                        'subject_id' => $subjectId,
                        'instructor_id' => $instructorId,
                        'calendar_event_id' => null,
                        'observation' => null,
                        'created_at' => $oldPattern->created_at ?? now(),
                        'updated_at' => $oldPattern->updated_at ?? now(),
                    ]);

                    $this->createMap('academic_schedule_frequency', $oldPattern->id, $frequency->id, [
                        'old_schedule_id' => $oldPattern->schedule_id,
                        'source' => 'schedule_pattern',
                    ]);

                    $createdFrequencies++;

                    if ($this->shouldGenerateEvents($academicSchedule->academic_year_id)) {
                        $eventsCreatedForFrequency = $this->createCalendarEventsForFrequency(
                            $academicSchedule,
                            $frequency
                        );

                        $createdEvents += $eventsCreatedForFrequency;
                    }
                }

                DB::commit();
            } catch (\Throwable $e) {
                DB::rollBack();

                $failed++;
                $this->error("Schedule {$oldSchedule->id}: {$e->getMessage()}");
            }
        }

        $this->table(
            ['Created schedules', 'Created frequencies', 'Created events', 'Skipped schedules', 'Failed'],
            [[$createdSchedules, $createdFrequencies, $createdEvents, $skippedSchedules, $failed]]
        );

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function fresh(): void
    {
        $this->warn('Fresh enabled. Deleting migrated academic schedules, frequencies and generated events...');

        $scheduleIds = MigrationIdMap::query()
            ->where('entity', 'academic_schedule')
            ->pluck('new_id');

        if ($scheduleIds->isNotEmpty()) {
            CalendarEvent::query()
                ->where('source', 'academic_schedule')
                ->whereIn('metadata->academic_schedule_id', $scheduleIds)
                ->forceDelete();

            AcademicScheduleFrequency::query()
                ->whereIn('academic_schedule_id', $scheduleIds)
                ->forceDelete();

            AcademicSchedule::query()
                ->whereIn('id', $scheduleIds)
                ->forceDelete();
        }

        MigrationIdMap::query()
            ->whereIn('entity', [
                'academic_schedule',
                'academic_schedule_frequency',
                'calendar_event',
            ])
            ->delete();
    }

    private function shouldGenerateEvents(string $academicYearId): bool
    {
        $academicYear = AcademicYear::query()->find($academicYearId);

        if (!$academicYear || !$academicYear->start_date) {
            return false;
        }

        return (int)CarbonImmutable::parse($academicYear->start_date)->year === 2026;
    }

    private function createCalendarEventsForFrequency(
        AcademicSchedule          $schedule,
        AcademicScheduleFrequency $frequency
    ): int
    {
        $schedule->loadMissing([
            'academicYear',
            'course',
            'specialty',
            'parallel',
            'shift',
            'modality',
        ]);

        $frequency->loadMissing([
            'classroom',
            'subject',
            'instructor.person',
        ]);

        $academicYear = AcademicYear::query()->find($schedule->academic_year_id);

        if (!$academicYear || !$academicYear->start_date || !$academicYear->end_date) {
            $this->warn("Academic year dates not found for schedule {$schedule->id}");
            return 0;
        }

        $startDate = CarbonImmutable::parse($academicYear->start_date)->startOfDay();
        $endDate = CarbonImmutable::parse($academicYear->end_date)->endOfDay();

        $currentDate = $this->firstDateForDayOfWeek(
            $startDate,
            (int)$frequency->day_of_week
        );

        $startTime = CarbonImmutable::parse($frequency->start_time)->format('H:i:s');
        $endTime = CarbonImmutable::parse($frequency->end_time)->format('H:i:s');

        $creatorId = $this->resolveCreatorId((string)$frequency->instructor_id);

        if (!$creatorId) {
            $this->warn("Creator user not found for instructor {$frequency->instructor_id}");
            return 0;
        }

        $eventTypeId = CalendarEventType::query()
            ->where('tenant_id', $schedule->tenant_id)
            ->where(function ($query) {
                $query->where('code', 'class_shift')
                    ->orWhere('code', 'CLASS_SHIFT')
                    ->orWhere('code', 'class')
                    ->orWhere('code', 'CLASS');
            })
            ->where('is_active', true)
            ->value('id');

        $firstEvent = null;
        $count = 0;

        while ($currentDate->lte($endDate)) {
            $startAt = CarbonImmutable::parse($currentDate->format('Y-m-d') . ' ' . $startTime);
            $endAt = CarbonImmutable::parse($currentDate->format('Y-m-d') . ' ' . $endTime);

            $event = CalendarEvent::query()->create([
                'tenant_id' => $schedule->tenant_id,
                'event_type_id' => $eventTypeId,
                'created_by' => $creatorId,
                'updated_by' => $creatorId,
                'title' => $this->buildCalendarTitle($schedule, $frequency),
                'description' => $this->buildCalendarDescription($schedule, $frequency),
                'location' => $frequency->classroom?->name,
                'url' => null,
                'start_at' => $startAt,
                'end_at' => $endAt,
                'all_day' => false,
                'timezone' => config('app.timezone', 'America/New_York'),
                'status' => 'confirmed',
                'visibility' => 'restricted',
                'source' => 'academic_schedule',
                'editable_by' => 'creator_only',
                'color' => null,
                'is_recurring' => false,
                'recurrence_rule' => null,
                'google_sync_enabled' => false,
                'google_last_synced_at' => null,
                'metadata' => [
                    'academic_schedule_id' => (string)$schedule->id,
                    'academic_schedule_frequency_id' => (string)$frequency->id,
                    'academic_year_id' => (string)$schedule->academic_year_id,
                    'course_id' => (string)$schedule->course_id,
                    'specialty_id' => $schedule->specialty_id ? (string)$schedule->specialty_id : null,
                    'parallel_id' => (string)$schedule->parallel_id,
                    'modality_id' => (string)$schedule->modality_id,
                    'shift_id' => (string)$schedule->shift_id,
                    'classroom_id' => (string)$frequency->classroom_id,
                    'subject_id' => (string)$frequency->subject_id,
                    'instructor_id' => (string)$frequency->instructor_id,
                    'day_of_week' => (int)$frequency->day_of_week,
                    'generated_from_migration' => true,
                ],
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->createMap('calendar_event', $event->id, $event->id, [
                'academic_schedule_id' => (string)$schedule->id,
                'academic_schedule_frequency_id' => (string)$frequency->id,
                'generated_from_migration' => true,
            ]);

            if (!$firstEvent) {
                $firstEvent = $event;
            }

            $count++;

            $currentDate = $currentDate->addWeek();
        }

        if ($firstEvent) {
            $frequency->forceFill([
                'calendar_event_id' => $firstEvent->id,
            ])->save();
        }

        return $count;
    }

    private function firstDateForDayOfWeek(CarbonImmutable $startDate, int $dayOfWeek): CarbonImmutable
    {
        $current = $startDate;

        while ((int)$current->isoWeekday() !== $dayOfWeek) {
            $current = $current->addDay();
        }

        return $current;
    }

    private function secondsToTime(?int $seconds): string
    {
        $seconds = (int)($seconds ?? 0);

        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);

        return sprintf('%02d:%02d:00', $hours, $minutes);
    }

    private function normalizeDayOfWeek($value): ?int
    {
        $value = trim((string)$value);

        if (is_numeric($value)) {
            $day = (int)$value;

            return $day >= 1 && $day <= 7 ? $day : null;
        }

        $normalized = str($value)->ascii()->lower()->trim()->toString();

        return match ($normalized) {
            'monday', 'lunes', 'mon', 'lun' => 1,
            'tuesday', 'martes', 'tue', 'mar' => 2,
            'wednesday', 'miercoles', 'miércoles', 'wed', 'mie', 'mié' => 3,
            'thursday', 'jueves', 'thu', 'jue' => 4,
            'friday', 'viernes', 'fri', 'vie' => 5,
            'saturday', 'sabado', 'sábado', 'sat', 'sab', 'sáb' => 6,
            'sunday', 'domingo', 'sun', 'dom' => 7,
            default => null,
        };
    }

    private function resolveCreatorId(string $instructorId): ?string
    {
        $instructor = Instructor::query()
            ->with('person')
            ->find($instructorId);

        if ($instructor?->person_id) {
            $userId = User::query()
                ->where('person_id', $instructor->person_id)
                ->value('id');

            if ($userId) {
                return $userId;
            }
        }

        return User::query()
            ->orderBy('created_at')
            ->value('id');
    }

    private function buildCalendarTitle(
        AcademicSchedule          $schedule,
        AcademicScheduleFrequency $frequency
    ): string
    {
        return trim(sprintf(
            '%s - %s %s',
            $frequency->subject?->name ?? 'Class',
            $schedule->course?->name ?? '',
            $schedule->parallel?->name ? '(' . $schedule->parallel->name . ')' : ''
        ));
    }

    private function buildCalendarDescription(
        AcademicSchedule          $schedule,
        AcademicScheduleFrequency $frequency
    ): string
    {
        return trim(implode(PHP_EOL, array_filter([
            'Academic schedule class',
            'Course: ' . ($schedule->course?->name ?? ''),
            'Specialty: ' . ($schedule->specialty?->name ?? ''),
            'Parallel: ' . ($schedule->parallel?->name ?? ''),
            'Shift: ' . ($schedule->shift?->name ?? ''),
            'Modality: ' . ($schedule->modality?->name ?? ''),
            'Subject: ' . ($frequency->subject?->name ?? ''),
            'Instructor: ' . ($frequency->instructor?->person?->full_name ?? ''),
            $schedule->general_observation,
            $frequency->observation,
        ])));
    }

    private function mappedId(string $entity, ?string $oldId): ?string
    {
        if (!$oldId) {
            return null;
        }

        return MigrationIdMap::query()
            ->where('entity', $entity)
            ->where('old_id', $oldId)
            ->value('new_id');
    }

    private function createMap(string $entity, string $oldId, string $newId, array $metadata = []): void
    {
        MigrationIdMap::query()->updateOrCreate(
            [
                'entity' => $entity,
                'old_id' => $oldId,
            ],
            [
                'new_id' => $newId,
                'metadata' => $metadata,
            ]
        );
    }
}
