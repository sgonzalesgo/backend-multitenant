<?php

namespace App\Repositories\Dashboard;

use App\Models\Academic\AcademicSchedule;
use App\Models\Academic\AttendanceRecord;
use App\Models\Academic\Enrollment;
use App\Models\Academic\Student;
use App\Models\Academic\StudentLegalRepresentative;
use App\Models\Administration\Tenant;
use App\Models\Calendar\CalendarEvent;
use Carbon\CarbonImmutable;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class StudentDashboardRepository
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
                'tenant' => __('messages.students.tenant_not_resolved'),
            ]);
        }

        return $tenantId;
    }

    public function show(Student $student, array $filters = []): array
    {
        $tenantId = $this->requireTenantId();

        if ((string) $student->tenant_id !== $tenantId) {
            abort(404);
        }

        $student->load([
            'person:id,full_name,email,phone,legal_id,birthday,photo',
        ]);

        $enrollments = $this->getEnrollments($tenantId, $student);

        $selectedEnrollment = $this->resolveSelectedEnrollment(
            $enrollments,
            Arr::get($filters, 'academic_year_id')
        );

        return [
            'academic_years' => $this->mapAcademicYears($enrollments),
            'selected_academic_year_id' => $selectedEnrollment?->academic_year_id,

            'student' => $this->mapStudent($student, $selectedEnrollment),
            'enrollment' => $this->mapEnrollment($selectedEnrollment),
            'summary' => $this->getSummary($tenantId, $student, $selectedEnrollment),
            'attendance' => $this->getAttendanceSummary($tenantId, $student, $selectedEnrollment),
            'schedule' => $this->getSchedule($tenantId, $selectedEnrollment),
            'grades' => [],
            'performance_series' => [],
            'tasks' => [],
            'payments' => null,
            'announcements' => $this->getAnnouncements($tenantId, $selectedEnrollment),
            'guardians' => $this->getGuardians($tenantId, $student),
            'alerts' => $this->getAlerts($tenantId, $student, $selectedEnrollment),
            'timeline' => $this->getTimeline($tenantId, $student, $selectedEnrollment),
        ];
    }

    /**
     * @throws ValidationException
     */
    public function findAuthenticatedStudent(): Student
    {
        $tenantId = $this->requireTenantId();

        $user = auth()->user();

        if (! $user || empty($user->person_id)) {
            throw ValidationException::withMessages([
                'student' => __('messages.students.authenticated_student_not_found'),
            ]);
        }

        $student = Student::query()
            ->where('tenant_id', $tenantId)
            ->where('person_id', $user->person_id)
            ->first();

        if (! $student) {
            throw ValidationException::withMessages([
                'student' => __('messages.students.authenticated_student_not_found'),
            ]);
        }

        return $student;
    }

    protected function getEnrollments(string $tenantId, Student $student): Collection
    {
        return Enrollment::query()
            ->with([
                'academicYear:id,tenant_id,code,name,start_date,end_date,is_active,is_current',
                'course:id,tenant_id,educational_level_id,code,name,level_number,status',
                'course.educationalLevel:id,tenant_id,code,name',
                'specialty:id,tenant_id,code,name,is_active',
                'parallel:id,tenant_id,code,name,is_active',
                'modality:id,tenant_id,code,name,is_active',
                'shift:id,tenant_id,code,name,is_active',
                'enrollmentStatus:id,code,name,is_active',
            ])
            ->where('tenant_id', $tenantId)
            ->where('student_id', $student->id)
            ->orderByDesc('created_at')
            ->get();
    }

    protected function resolveSelectedEnrollment(Collection $enrollments, ?string $academicYearId): ?Enrollment
    {
        if ($academicYearId) {
            return $enrollments->firstWhere('academic_year_id', $academicYearId);
        }

        return $enrollments->first(fn ($enrollment) => (bool) $enrollment->academicYear?->is_current)
            ?? $enrollments->firstWhere('is_active', true)
            ?? $enrollments->first();
    }

    protected function mapAcademicYears(Collection $enrollments): array
    {
        return $enrollments
            ->filter(fn ($enrollment) => $enrollment->academicYear)
            ->map(fn ($enrollment) => [
                'id' => (string) $enrollment->academicYear->id,
                'code' => $enrollment->academicYear->code,
                'label' => $enrollment->academicYear->name,
                'name' => $enrollment->academicYear->name,
                'start_date' => optional($enrollment->academicYear->start_date)?->toDateString(),
                'end_date' => optional($enrollment->academicYear->end_date)?->toDateString(),
                'current' => (bool) $enrollment->academicYear->is_current,
                'enrollment_id' => (string) $enrollment->id,
            ])
            ->values()
            ->all();
    }

    protected function mapStudent(Student $student, ?Enrollment $enrollment): array
    {
        $person = $student->person;

        return [
            'id' => (string) $student->id,
            'person_id' => $person?->id ? (string) $person->id : null,
            'fullName' => $person?->full_name,
            'full_name' => $person?->full_name,
            'code' => $student->student_code,
            'student_code' => $student->student_code,
            'email' => $person?->email,
            'phone' => $person?->phone,
            'legal_id' => $person?->legal_id,
            'birthDate' => optional($person?->birthday)?->format('d/m/Y'),
            'birthday' => optional($person?->birthday)?->toDateString(),
            'avatar' => $person?->photo,
            'status' => $student->status,

            'course' => $enrollment?->course?->name,
            'parallel' => $enrollment?->parallel?->name,
            'section' => $enrollment?->shift?->name,
            'shift' => $enrollment?->shift?->name,
            'modality' => $enrollment?->modality?->name,
            'specialty' => $enrollment?->specialty?->name,
            'enrollmentStatus' => $enrollment?->enrollmentStatus?->name,
            'enrollment_status' => $enrollment?->enrollmentStatus?->name,
        ];
    }

    protected function mapEnrollment(?Enrollment $enrollment): ?array
    {
        if (! $enrollment) {
            return null;
        }

        return [
            'id' => (string) $enrollment->id,
            'enrollment_code' => $enrollment->enrollment_code,
            'academic_year_id' => (string) $enrollment->academic_year_id,
            'course_id' => (string) $enrollment->course_id,
            'specialty_id' => $enrollment->specialty_id ? (string) $enrollment->specialty_id : null,
            'parallel_id' => (string) $enrollment->parallel_id,
            'modality_id' => $enrollment->modality_id ? (string) $enrollment->modality_id : null,
            'shift_id' => (string) $enrollment->shift_id,
            'is_active' => (bool) $enrollment->is_active,
            'is_new' => (bool) $enrollment->is_new,
            'is_conditional' => (bool) $enrollment->is_conditional,
        ];
    }

    protected function getSummary(string $tenantId, Student $student, ?Enrollment $enrollment): array
    {
        $attendance = $this->getAttendanceSummary($tenantId, $student, $enrollment);

        $schedule = $this->getSchedule($tenantId, $enrollment);

        return [
            'attendance' => $attendance['percentage'],
            'subjects' => collect($schedule)
                ->pluck('subject_id')
                ->filter()
                ->unique()
                ->count(),
            'average' => null,
            'pendingTasks' => 0,
            'behavior' => null,
            'payments' => null,
            'yearProgress' => $this->calculateAcademicYearProgress($enrollment),
        ];
    }

    protected function getAttendanceSummary(string $tenantId, Student $student, ?Enrollment $enrollment): array
    {
        if (! $enrollment) {
            return [
                'present' => 0,
                'absent' => 0,
                'late' => 0,
                'excused' => 0,
                'total' => 0,
                'percentage' => 0,
            ];
        }

        $records = AttendanceRecord::query()
            ->where('tenant_id', $tenantId)
            ->where('student_id', $student->id)
            ->where('enrollment_id', $enrollment->id)
            ->selectRaw("
                SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present_count,
                SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent_count,
                SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as late_count,
                SUM(CASE WHEN status = 'excused' THEN 1 ELSE 0 END) as excused_count,
                COUNT(*) as total_count
            ")
            ->first();

        $present = (int) ($records->present_count ?? 0);
        $absent = (int) ($records->absent_count ?? 0);
        $late = (int) ($records->late_count ?? 0);
        $excused = (int) ($records->excused_count ?? 0);
        $total = (int) ($records->total_count ?? 0);

        return [
            'present' => $present,
            'absent' => $absent,
            'late' => $late,
            'excused' => $excused,
            'total' => $total,
            'percentage' => $total > 0
                ? round((($present + $excused) / $total) * 100)
                : 0,
        ];
    }

    protected function getSchedule(string $tenantId, ?Enrollment $enrollment): array
    {
        if (! $enrollment) {
            return [];
        }

        return AcademicSchedule::query()
            ->with([
                'frequencies.subject:id,tenant_id,code,name,is_active',
                'frequencies.instructor:id,person_id',
                'frequencies.instructor.person:id,full_name,email,photo',
                'frequencies.classroom:id,tenant_id,code,name,location',
            ])
            ->where('tenant_id', $tenantId)
            ->where('academic_year_id', $enrollment->academic_year_id)
            ->where('course_id', $enrollment->course_id)
            ->where('parallel_id', $enrollment->parallel_id)
            ->where('shift_id', $enrollment->shift_id)
            ->when(
                $enrollment->specialty_id,
                fn ($query) => $query->where('specialty_id', $enrollment->specialty_id),
                fn ($query) => $query->whereNull('specialty_id')
            )
            ->when(
                $enrollment->modality_id,
                fn ($query) => $query->where('modality_id', $enrollment->modality_id)
            )
            ->whereIn('status', ['draft', 'in_progress', 'accepted'])
            ->get()
            ->flatMap(fn ($schedule) => $schedule->frequencies)
            ->sortBy([
                ['day_of_week', 'asc'],
                ['start_time', 'asc'],
            ])
            ->map(fn ($frequency) => [
                'day_of_week' => (int) $frequency->day_of_week,
                'time' => $this->formatTimeRange($frequency->start_time, $frequency->end_time),
                'start_time' => $frequency->start_time,
                'end_time' => $frequency->end_time,
                'subject' => $frequency->subject?->name,
                'subject_id' => $frequency->subject?->id ? (string) $frequency->subject->id : null,
                'teacher' => $frequency->instructor?->person?->full_name,
                'instructor_id' => $frequency->instructor?->id ? (string) $frequency->instructor->id : null,
                'classroom' => $frequency->classroom?->name ?? $frequency->classroom?->code,
                'classroom_id' => $frequency->classroom?->id ? (string) $frequency->classroom->id : null,
            ])
            ->values()
            ->all();
    }

    protected function getAnnouncements(string $tenantId, ?Enrollment $enrollment): array
    {
        if (! $enrollment) {
            return [];
        }

        return CalendarEvent::query()
            ->with('eventType:id,code,name,color,icon')
            ->where('tenant_id', $tenantId)
            ->where('status', 'confirmed')
            ->where('source', '!=', 'academic_schedule')
            ->where(function ($query) use ($enrollment) {
                $query
                    ->where('visibility', 'public_tenant')
                    ->orWhere('metadata->academic_year_id', (string) $enrollment->academic_year_id)
                    ->orWhere('metadata->course_id', (string) $enrollment->course_id)
                    ->orWhere('metadata->parallel_id', (string) $enrollment->parallel_id);
            })
            ->orderByDesc('start_at')
            ->limit(5)
            ->get()
            ->map(fn ($event) => [
                'id' => (string) $event->id,
                'title' => $event->title,
                'body' => $event->description,
                'date' => optional($event->start_at)?->format('d/m/Y'),
                'start_at' => optional($event->start_at)?->toIso8601String(),
                'event_type' => $event->eventType?->name,
            ])
            ->values()
            ->all();
    }

    protected function getGuardians(string $tenantId, Student $student): array
    {
        return StudentLegalRepresentative::query()
            ->with([
                'legalRepresentative:id,tenant_id,person_id,status',
                'legalRepresentative.person:id,full_name,email,phone,photo',
            ])
            ->where('tenant_id', $tenantId)
            ->where('student_id', $student->id)
            ->get()
            ->map(function ($relationship) {
                $person = $relationship->legalRepresentative?->person;

                return [
                    'id' => (string) $relationship->id,
                    'legal_representative_id' => $relationship->legal_representative_id
                        ? (string) $relationship->legal_representative_id
                        : null,
                    'name' => $person?->full_name,
                    'relation' => $relationship->relationship_type,
                    'relationship_type' => $relationship->relationship_type,
                    'description' => $relationship->description,
                    'phone' => $person?->phone,
                    'email' => $person?->email,
                    'avatar' => $person?->photo,
                    'is_billable' => (bool) $relationship->is_billable,
                    'is_emergency_contact' => (bool) $relationship->is_emergency_contact,
                ];
            })
            ->values()
            ->all();
    }

    protected function getAlerts(string $tenantId, Student $student, ?Enrollment $enrollment): array
    {
        $alerts = [];

        $attendance = $this->getAttendanceSummary($tenantId, $student, $enrollment);

        if ($attendance['absent'] > 0) {
            $alerts[] = [
                'color' => 'warning',
                'icon' => 'ri-alert-line',
                'text' => "Tienes {$attendance['absent']} ausencias registradas en este año académico.",
            ];
        }

        if ($attendance['late'] > 0) {
            $alerts[] = [
                'color' => 'info',
                'icon' => 'ri-time-line',
                'text' => "Tienes {$attendance['late']} tardanzas registradas.",
            ];
        }

        if (empty($alerts)) {
            $alerts[] = [
                'color' => 'success',
                'icon' => 'ri-checkbox-circle-line',
                'text' => 'No hay alertas importantes para este año académico.',
            ];
        }

        return $alerts;
    }

    protected function getTimeline(string $tenantId, Student $student, ?Enrollment $enrollment): array
    {
        if (! $enrollment) {
            return [];
        }

        $attendanceItems = AttendanceRecord::query()
            ->with([
                'attendanceSession:id,attendance_date,subject_id,status',
                'attendanceSession.subject:id,name,code',
            ])
            ->where('tenant_id', $tenantId)
            ->where('student_id', $student->id)
            ->where('enrollment_id', $enrollment->id)
            ->latest('updated_at')
            ->limit(5)
            ->get()
            ->map(fn ($record) => [
                'color' => $record->status === 'present' ? 'success' : 'warning',
                'title' => 'Asistencia registrada',
                'body' => trim(sprintf(
                    '%s - %s',
                    $record->attendanceSession?->subject?->name ?? 'Asignatura',
                    ucfirst((string) $record->status)
                )),
                'time' => optional($record->updated_at)?->diffForHumans(),
                'created_at' => optional($record->updated_at)?->toIso8601String(),
            ]);

        return $attendanceItems->values()->all();
    }

    protected function calculateAcademicYearProgress(?Enrollment $enrollment): int
    {
        if (! $enrollment?->academicYear?->start_date || ! $enrollment?->academicYear?->end_date) {
            return 0;
        }

        $start = CarbonImmutable::parse($enrollment->academicYear->start_date)->startOfDay();
        $end = CarbonImmutable::parse($enrollment->academicYear->end_date)->endOfDay();
        $today = now();

        if ($today->lessThanOrEqualTo($start)) {
            return 0;
        }

        if ($today->greaterThanOrEqualTo($end)) {
            return 100;
        }

        $totalDays = max($start->diffInDays($end), 1);
        $elapsedDays = $start->diffInDays($today);

        return (int) round(($elapsedDays / $totalDays) * 100);
    }

    protected function formatTimeRange(?string $start, ?string $end): string
    {
        if (! $start || ! $end) {
            return '';
        }

        return CarbonImmutable::parse($start)->format('H:i') . ' - ' . CarbonImmutable::parse($end)->format('H:i');
    }
}
