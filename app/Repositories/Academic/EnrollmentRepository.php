<?php

namespace App\Repositories\Academic;

use App\Models\Academic\Enrollment;
use App\Models\Academic\Student;
use App\Models\Academic\StudentLegalRepresentative;
use App\Models\Administration\Tenant;
use App\Notifications\Academic\EnrollmentCreatedNotification;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use App\Models\Academic\StudentUserLink;

class EnrollmentRepository
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
        $tenantId = $this->resolveCurrentTenantId();

        if (! $tenantId) {
            throw ValidationException::withMessages([
                'tenant' => __('messages.enrollments.tenant_not_resolved'),
            ]);
        }

        $rawQ = Arr::get($filters, 'q', '');
        $sort = Arr::get($filters, 'sort', 'created_at');
        $dir = strtolower((string) Arr::get($filters, 'dir', 'desc')) === 'asc' ? 'asc' : 'desc';
        $perPage = max(1, min((int) Arr::get($filters, 'per_page', 15), 100));

        if (! in_array($sort, [
            'enrollment_code',
            'created_at',
            'updated_at',
            'submitted_at',
            'is_active',
            'is_new',
            'is_conditional',
        ], true)) {
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

        $student = trim((string) Arr::get($columns, 'student', ''));
        $studentCode = trim((string) Arr::get($columns, 'student_code', ''));
        $enrollmentCode = trim((string) Arr::get($columns, 'enrollment_code', ''));

        return Enrollment::query()
            ->with($this->relations())
            ->where('tenant_id', $tenantId)
            ->when($global !== '', function ($query) use ($global) {
                $query->where(function ($q) use ($global) {
                    $q->where('enrollment_code', 'ilike', "%{$global}%")
                        ->orWhere('observation', 'ilike', "%{$global}%")
                        ->orWhereHas('student', function ($studentQuery) use ($global) {
                            $studentQuery->where('student_code', 'ilike', "%{$global}%")
                                ->orWhereHas('person', function ($personQuery) use ($global) {
                                    $personQuery->where('full_name', 'ilike', "%{$global}%")
                                        ->orWhere('email', 'ilike', "%{$global}%")
                                        ->orWhere('phone', 'ilike', "%{$global}%")
                                        ->orWhere('legal_id', 'ilike', "%{$global}%");
                                });
                        })
                        ->orWhereHas('course', fn ($courseQuery) => $courseQuery->where('name', 'ilike', "%{$global}%"))
                        ->orWhereHas('parallel', fn ($parallelQuery) => $parallelQuery->where('name', 'ilike', "%{$global}%"))
                        ->orWhereHas('shift', fn ($shiftQuery) => $shiftQuery->where('name', 'ilike', "%{$global}%"))
                        ->orWhereHas('enrollmentStatus', fn ($statusQuery) => $statusQuery->where('name', 'ilike', "%{$global}%"));
                });
            })
            ->when($student !== '', function ($query) use ($student) {
                $query->whereHas('student.person', function ($q) use ($student) {
                    $q->where('full_name', 'ilike', "%{$student}%")
                        ->orWhere('legal_id', 'ilike', "%{$student}%");
                });
            })
            ->when($studentCode !== '', function ($query) use ($studentCode) {
                $query->whereHas('student', fn ($q) => $q->where('student_code', 'ilike', "%{$studentCode}%"));
            })
            ->when($enrollmentCode !== '', fn ($query) => $query->where('enrollment_code', 'ilike', "%{$enrollmentCode}%"))
            ->when(Arr::get($columns, 'academic_year_id'), fn ($query, $value) => $query->where('academic_year_id', $value))
            ->when(Arr::get($columns, 'course_id'), fn ($query, $value) => $query->where('course_id', $value))
            ->when(Arr::get($columns, 'parallel_id'), fn ($query, $value) => $query->where('parallel_id', $value))
            ->when(Arr::get($columns, 'shift_id'), fn ($query, $value) => $query->where('shift_id', $value))
            ->when(Arr::get($columns, 'enrollment_status_id'), fn ($query, $value) => $query->where('enrollment_status_id', $value))
            ->orderBy($sort, $dir)
            ->paginate($perPage);
    }

    public function active(array $filters = []): LengthAwarePaginator
    {
        $tenantId = $this->resolveCurrentTenantId();

        if (! $tenantId) {
            throw ValidationException::withMessages([
                'tenant' => __('messages.enrollments.tenant_not_resolved'),
            ]);
        }

        $perPage = max(1, min((int) Arr::get($filters, 'per_page', 15), 100));

        return Enrollment::query()
            ->with($this->relations())
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->whereHas('student.person', function ($query) {
                $query->whereNull('deleted_at')
                    ->whereNull('deceased_at');
            })
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    public function find(Enrollment $enrollment): Enrollment
    {
        $tenantId = $this->resolveCurrentTenantId();

        if ($tenantId && (string) $enrollment->tenant_id !== $tenantId) {
            abort(404);
        }

        return $enrollment->load($this->relations());
    }

    /**
     * @throws ValidationException
     */
    public function create(array $data): Enrollment
    {
        $tenantId = $this->resolveCurrentTenantId();

        if (! $tenantId) {
            throw ValidationException::withMessages([
                'tenant' => __('messages.enrollments.tenant_not_resolved'),
            ]);
        }

        $enrollment = DB::transaction(function () use ($data, $tenantId) {
            $student = $this->resolveStudent($data, $tenantId);

            $this->ensureEnrollmentDoesNotExist($data, $tenantId);

            $this->syncLegalRepresentatives($student, $data, $tenantId);

            $enrollment = Enrollment::query()->create([
                'tenant_id' => $tenantId,
                'enrollment_code' => $this->generateEnrollmentCode(),
                'student_id' => $student->id,
                ...$this->extractEnrollmentPayload($data),
                'submitted_at' => Arr::get($data, 'submitted_at') ?? now(),
            ]);

            return $this->find($enrollment->refresh());
        });

        $this->notifyEnrollmentCreated($enrollment);

        return $this->find($enrollment->refresh());
    }

    public function update(Enrollment $enrollment, array $data): Enrollment
    {
        $tenantId = $this->resolveCurrentTenantId();

        if ($tenantId && (string) $enrollment->tenant_id !== $tenantId) {
            abort(404);
        }

        return DB::transaction(function () use ($enrollment, $data, $tenantId) {
            if (
                array_key_exists('student_id', $data) ||
                array_key_exists('academic_year_id', $data) ||
                array_key_exists('course_id', $data) ||
                array_key_exists('parallel_id', $data) ||
                array_key_exists('shift_id', $data)
            ) {
                $this->ensureEnrollmentDoesNotExistForUpdate($enrollment, $data, $tenantId);
            }

            if (array_key_exists('representatives', $data)) {
                $student = $enrollment->student;

                if ($student) {
                    $this->syncLegalRepresentatives($student, $data, $tenantId);
                }
            }

            $enrollment->fill($this->extractEnrollmentPayload($data));
            $enrollment->save();

            return $this->find($enrollment->refresh());
        });
    }

    public function delete(Enrollment $enrollment): void
    {
        $tenantId = $this->resolveCurrentTenantId();

        if ($tenantId && (string) $enrollment->tenant_id !== $tenantId) {
            abort(404);
        }

        $enrollment->delete();
    }

    protected function relations(): array
    {
        return [
            'tenant:id,name',

            'student:id,tenant_id,person_id,student_code,status',
            'student.person:id,full_name,email,phone,legal_id,legal_id_type,photo,birthday,gender,address,country_id,state_id,city_id',
            'student.person.country:id,code,name',
            'student.person.state:id,country_id,code,name',
            'student.person.city:id,state_id,name',
            'academicYear:id,name',
            'course:id,name,code',
            'parallel:id,name',
            'shift:id,name',
            'enrollmentStatus:id,name',
            'assignedUser:id,person_id,name,email,status',
            'student.legalRepresentativeRelationships.legalRepresentative:id,tenant_id,person_id,status',
            'student.legalRepresentativeRelationships.legalRepresentative.person:id,full_name,email,phone,legal_id,legal_id_type',
        ];
    }

    protected function extractEnrollmentPayload(array $data): array
    {
        return Arr::only($data, [
            'student_id',
            'academic_year_id',
            'course_id',
            'parallel_id',
            'shift_id',
            'enrollment_status_id',
            'assigned_user_id',
            'is_new',
            'is_conditional',
            'is_active',
            'observation',
            'submitted_at',
        ]);
    }

    protected function resolveStudent(array $data, string $tenantId): Student
    {
        $student = Student::query()
            ->where('tenant_id', $tenantId)
            ->where('id', Arr::get($data, 'student_id'))
            ->first();

        if (! $student) {
            throw ValidationException::withMessages([
                'student_id' => __('messages.students.not_found'),
            ]);
        }

        return $student;
    }

    protected function generateEnrollmentCode(): string
    {
        for ($attempt = 0; $attempt < 10; $attempt++) {
            $code = 'ENR-'.now()->format('Y').'-'.Str::upper(Str::random(10));

            if (! Enrollment::query()->where('enrollment_code', $code)->exists()) {
                return $code;
            }
        }

        throw ValidationException::withMessages([
            'enrollment_code' => __('messages.enrollments.code_generation_failed'),
        ]);
    }

    protected function ensureEnrollmentDoesNotExist(array $data, string $tenantId): void
    {
        $query = Enrollment::query()
            ->where('tenant_id', $tenantId)
            ->where('student_id', Arr::get($data, 'student_id'))
            ->where('academic_year_id', Arr::get($data, 'academic_year_id'));

        $this->whereNullable($query, 'course_id', Arr::get($data, 'course_id'));
        $this->whereNullable($query, 'parallel_id', Arr::get($data, 'parallel_id'));
        $this->whereNullable($query, 'shift_id', Arr::get($data, 'shift_id'));

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'student_id' => __('messages.enrollments.already_exists'),
            ]);
        }
    }

    protected function ensureEnrollmentDoesNotExistForUpdate(
        Enrollment $enrollment,
        array $data,
        string $tenantId
    ): void {
        $studentId = Arr::get($data, 'student_id', $enrollment->student_id);
        $academicYearId = Arr::get($data, 'academic_year_id', $enrollment->academic_year_id);
        $courseId = Arr::get($data, 'course_id', $enrollment->course_id);
        $parallelId = Arr::get($data, 'parallel_id', $enrollment->parallel_id);
        $shiftId = Arr::get($data, 'shift_id', $enrollment->shift_id);

        $query = Enrollment::query()
            ->where('tenant_id', $tenantId)
            ->where('id', '!=', $enrollment->id)
            ->where('student_id', $studentId)
            ->where('academic_year_id', $academicYearId);

        $this->whereNullable($query, 'course_id', $courseId);
        $this->whereNullable($query, 'parallel_id', $parallelId);
        $this->whereNullable($query, 'shift_id', $shiftId);

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'student_id' => __('messages.enrollments.already_exists'),
            ]);
        }
    }

    protected function whereNullable($query, string $column, mixed $value): void
    {
        if (is_null($value) || $value === '') {
            $query->whereNull($column);

            return;
        }

        $query->where($column, $value);
    }

    protected function notifyEnrollmentCreated(Enrollment $enrollment): void
    {
        $enrollment->load($this->relations());

        $studentNotified = $this->notifyStudent($enrollment);
        $representativesNotified = $this->notifyRepresentatives($enrollment);

        $updates = [];

        if ($studentNotified) {
            $updates['student_email_sent_at'] = now();
        }

        if ($representativesNotified) {
            $updates['representatives_email_sent_at'] = now();
        }

        if (! empty($updates)) {
            $enrollment->forceFill($updates)->save();
        }
    }

    protected function notifyStudent(Enrollment $enrollment): bool
    {
        $person = $enrollment->student?->person;

        if (! $person || blank($person->email)) {
            return false;
        }

        try {
            Notification::route('mail', [
                $person->email => $person->full_name,
            ])->notify(new EnrollmentCreatedNotification($enrollment, 'student'));

            return true;
        } catch (\Throwable $exception) {
            Log::error('Enrollment student notification failed.', [
                'enrollment_id' => $enrollment->id,
                'student_id' => $enrollment->student_id,
                'email' => $person->email,
                'error' => $exception->getMessage(),
            ]);

            return false;
        }
    }

    protected function notifyRepresentatives(Enrollment $enrollment): bool
    {
        $relationships = StudentLegalRepresentative::query()
            ->with([
                'legalRepresentative.person.user:id,person_id,name,email,status',
            ])
            ->where('tenant_id', $enrollment->tenant_id)
            ->where('student_id', $enrollment->student_id)
            ->get();

        $notified = false;

        foreach ($relationships as $relationship) {
            $person = $relationship->legalRepresentative?->person;

            if (! $person || blank($person->email)) {
                continue;
            }

            try {
                $studentUserLink = $this->createStudentUserLink($enrollment, $relationship);

                Notification::route('mail', [
                    $person->email => $person->full_name,
                ])->notify(
                    new EnrollmentCreatedNotification(
                        $enrollment,
                        'representative',
                        $studentUserLink
                    )
                );

                $notified = true;
            } catch (\Throwable $exception) {
                Log::error('Enrollment representative notification failed.', [
                    'enrollment_id' => $enrollment->id,
                    'student_id' => $enrollment->student_id,
                    'representative_id' => $relationship->legal_representative_id,
                    'email' => $person->email,
                    'error' => $exception->getMessage(),
                ]);
            }
        }

        return $notified;
    }

    protected function syncLegalRepresentatives(Student $student, array $data, string $tenantId): void
    {
        $representatives = Arr::get($data, 'representatives', []);

        if (! is_array($representatives) || empty($representatives)) {
            return;
        }

        StudentLegalRepresentative::withTrashed()
            ->where('tenant_id', $tenantId)
            ->where('student_id', $student->id)
            ->forceDelete();

        foreach ($representatives as $representative) {
            StudentLegalRepresentative::query()->create([
                'tenant_id' => $tenantId,
                'student_id' => $student->id,
                'legal_representative_id' => Arr::get($representative, 'legal_representative_id'),
                'relationship_type' => Arr::get($representative, 'relationship_type'),
                'description' => Arr::get($representative, 'description'),
                'is_billable' => filter_var(Arr::get($representative, 'is_billable', false), FILTER_VALIDATE_BOOLEAN),
                'is_emergency_contact' => filter_var(Arr::get($representative, 'is_emergency_contact', false), FILTER_VALIDATE_BOOLEAN),
            ]);
        }
    }

    protected function createStudentUserLink(
        Enrollment $enrollment,
        StudentLegalRepresentative $relationship
    ): ?StudentUserLink {
        $representative = $relationship->legalRepresentative;
        $person = $representative?->person;
        $student = $enrollment->student;

        if (! $representative || ! $person || blank($person->email) || ! $student) {
            return null;
        }

        return StudentUserLink::query()->create([
            'tenant_id' => $enrollment->tenant_id,
            'student_id' => $enrollment->student_id,
            'legal_representative_id' => $representative->id,
            'user_id' => $person->user?->id,
            'token' => $this->generateStudentUserLinkToken(),
            'student_code' => $student->student_code,
            'enrollment_code' => $enrollment->enrollment_code,
            'email' => $person->email,
            'status' => 'pending',
            'expires_at' => now()->addDays(7),
        ]);
    }

    protected function generateStudentUserLinkToken(): string
    {
        do {
            $token = Str::random(80);
        } while (
            StudentUserLink::query()
                ->where('token', $token)
                ->exists()
        );

        return $token;
    }
}
