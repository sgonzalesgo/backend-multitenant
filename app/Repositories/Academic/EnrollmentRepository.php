<?php

namespace App\Repositories\Academic;

use App\Models\Academic\Enrollment;
use App\Models\Academic\LegalRepresentative;
use App\Models\Academic\Student;
use App\Models\Academic\StudentLegalRepresentative;
use App\Models\Academic\StudentUserLink;
use App\Models\Administration\Tenant;
use App\Models\General\Person;
use App\Notifications\Academic\EnrollmentCreatedNotification;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class EnrollmentRepository
{
    protected string $disk = 'public';

    protected string $directory = 'persons';

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

    /**
     * @throws ValidationException
     */
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

        $paginator = Enrollment::query()
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

            ->when(
                $enrollmentCode !== '',
                fn ($query) => $query->where('enrollment_code', 'ilike', "%{$enrollmentCode}%")
            )

            ->when(
                Arr::get($columns, 'academic_year_id'),
                fn ($query, $value) => $query->where('academic_year_id', $value)
            )

            ->when(
                Arr::get($columns, 'course_id'),
                fn ($query, $value) => $query->where('course_id', $value)
            )

            ->when(
                Arr::get($columns, 'specialty_id'),
                fn ($query, $value) => $query->where('specialty_id', $value)
            )

            ->when(
                Arr::get($columns, 'parallel_id'),
                fn ($query, $value) => $query->where('parallel_id', $value)
            )

            ->when(
                Arr::get($columns, 'shift_id'),
                fn ($query, $value) => $query->where('shift_id', $value)
            )

            ->when(
                Arr::get($columns, 'enrollment_status_id'),
                fn ($query, $value) => $query->where('enrollment_status_id', $value)
            )

            ->orderBy($sort, $dir)
            ->paginate($perPage);

        $paginator->setCollection(
            $paginator->getCollection()->map(
                fn (Enrollment $enrollment) => $this->formatPreparedEnrollment($enrollment)
            )
        );

        return $paginator;
    }

    /**
     * @throws ValidationException
     */
    public function active(array $filters = []): LengthAwarePaginator
    {
        $tenantId = $this->resolveCurrentTenantId();

        if (! $tenantId) {
            throw ValidationException::withMessages([
                'tenant' => __('messages.enrollments.tenant_not_resolved'),
            ]);
        }

        $perPage = max(1, min((int) Arr::get($filters, 'per_page', 15), 100));

        $paginator = Enrollment::query()
            ->with($this->relations())
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)

            ->whereHas('student.person', function ($query) {
                $query->whereNull('deleted_at')
                    ->whereNull('deceased_at');
            })

            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        $paginator->setCollection(
            $paginator->getCollection()->map(
                fn (Enrollment $enrollment) => $this->formatPreparedEnrollment($enrollment)
            )
        );

        return $paginator;
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
    public function create(array $data): array
    {
        $tenantId = $this->resolveCurrentTenantId();

        if (! $tenantId) {
            throw ValidationException::withMessages([
                'tenant' => __('messages.enrollments.tenant_not_resolved'),
            ]);
        }

        $enrollment = DB::transaction(function () use ($data, $tenantId) {
            $student = $this->resolveOrCreateStudent($data, $tenantId);

            $data['student_id'] = $student->id;
            $data['representatives'] = $this->resolveOrCreateRepresentatives($data, $tenantId);

            $this->ensureEnrollmentDoesNotExist($data, $tenantId);
            $this->syncLegalRepresentatives($student, $data, $tenantId);

            $enrollment = Enrollment::query()->create([
                'tenant_id' => $tenantId,
                'enrollment_code' => $this->generateEnrollmentCode(),
                ...$this->extractEnrollmentPayload($data),
                'student_id' => $student->id,
                'submitted_at' => Arr::get($data, 'submitted_at') ?? now(),
            ]);

            return $enrollment->load($this->relations());
        });

        $this->notifyEnrollmentCreated($enrollment);

        return $this->formatPreparedEnrollment(
            $enrollment->refresh()->load($this->relations())
        );
    }

    public function update(Enrollment $enrollment, array $data): array
    {
        $tenantId = $this->resolveCurrentTenantId();

        if ($tenantId && (string) $enrollment->tenant_id !== $tenantId) {
            abort(404);
        }

        $updatedEnrollment = DB::transaction(function () use ($enrollment, $data, $tenantId) {
            $student = null;

            if (array_key_exists('student_id', $data) || array_key_exists('student', $data)) {
                $student = $this->resolveOrCreateStudent($data, $tenantId);
                $data['student_id'] = $student->id;
            }

            if (
                array_key_exists('student_id', $data) ||
                array_key_exists('student', $data) ||
                array_key_exists('academic_year_id', $data) ||
                array_key_exists('course_id', $data) ||
                array_key_exists('specialty_id', $data) ||
                array_key_exists('parallel_id', $data) ||
                array_key_exists('shift_id', $data)
            ) {
                $this->ensureEnrollmentDoesNotExistForUpdate($enrollment, $data, $tenantId);
            }

            $enrollment->fill($this->extractEnrollmentPayload($data));
            $enrollment->save();

            if (array_key_exists('representatives', $data)) {
                $student = $student ?: $enrollment->student;

                if ($student) {
                    $data['representatives'] = $this->resolveOrCreateRepresentatives($data, $tenantId);

                    $this->syncLegalRepresentatives($student, $data, $tenantId);
                }
            }

            return $enrollment->refresh()->load($this->relations());
        });

        return $this->formatPreparedEnrollment($updatedEnrollment);
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

            'student:id,tenant_id,person_id,student_code,status,notes',
            'student.person:id,full_name,email,phone,legal_id,legal_id_type,photo,birthday,gender,address,country_id,state_id,city_id,zip,marital_status,blood_group,nationality,deceased_at,status_changed_at',
            'student.person.country:id,code,name',
            'student.person.state:id,country_id,code,name',
            'student.person.city:id,state_id,name',

            'academicYear:id,name',
            'course:id,tenant_id,educational_level_id,code,name,level_number,status',
            'specialty:id,tenant_id,code,name,description,is_active',
            'course.educationalLevel:id,tenant_id,code,name,has_specialty,start_number,end_number,next_educational_level_id',
            'course.educationalLevel.specialties:id,tenant_id,code,name,description,is_active',
            'parallel:id,name',
            'shift:id,name',
            'enrollmentStatus:id,name',
            'assignedUser:id,person_id,name,email,status',

            'student.legalRepresentativeRelationships:id,tenant_id,student_id,legal_representative_id,relationship_type,description,is_billable,is_emergency_contact',
            'student.legalRepresentativeRelationships.legalRepresentative:id,tenant_id,person_id,status,notes',
            'student.legalRepresentativeRelationships.legalRepresentative.person:id,full_name,email,phone,legal_id,legal_id_type,photo,birthday,gender,address,country_id,state_id,city_id,zip,marital_status,blood_group,nationality,deceased_at,status_changed_at',
            'student.legalRepresentativeRelationships.legalRepresentative.person.country:id,code,name',
            'student.legalRepresentativeRelationships.legalRepresentative.person.state:id,country_id,code,name',
            'student.legalRepresentativeRelationships.legalRepresentative.person.city:id,state_id,name',
        ];
    }

    protected function extractEnrollmentPayload(array $data): array
    {
        return Arr::only($data, [
            'student_id',
            'academic_year_id',
            'course_id',
            'specialty_id',
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

    protected function resolveOrCreateStudent(array $data, string $tenantId): Student
    {
        $studentData = Arr::get($data, 'student', []);

        if ($studentId = Arr::get($data, 'student_id')) {
            $student = Student::query()
                ->where('tenant_id', $tenantId)
                ->where('id', $studentId)
                ->first();

            if (! $student) {
                throw ValidationException::withMessages([
                    'student_id' => __('messages.students.not_found'),
                ]);
            }

            if (! empty($studentData)) {
                $student->fill($this->extractStudentPayload($studentData));
                $student->save();

                if ($student->person && Arr::has($studentData, 'person')) {
                    $this->updatePersonIfNeeded($student->person, Arr::get($studentData, 'person', []));
                }
            }

            return $student;
        }

        $person = $this->resolveOrCreatePerson($studentData, $tenantId, 'student');

        $student = Student::query()
            ->where('tenant_id', $tenantId)
            ->where('person_id', $person->id)
            ->first();

        if ($student) {
            $student->fill($this->extractStudentPayload($studentData));
            $student->save();

            return $student;
        }

        return Student::query()->create([
            'tenant_id' => $tenantId,
            'person_id' => $person->id,
            'student_code' => $this->generateStudentCode($tenantId),
            ...$this->extractStudentPayload($studentData),
        ]);
    }

    protected function resolveOrCreateRepresentatives(array $data, string $tenantId): array
    {
        $representatives = Arr::get($data, 'representatives', []);

        if (! is_array($representatives) || empty($representatives)) {
            return [];
        }

        return collect($representatives)
            ->map(function (array $representative) use ($tenantId) {
                $legalRepresentative = $this->resolveOrCreateLegalRepresentative($representative, $tenantId);

                return [
                    'legal_representative_id' => $legalRepresentative->id,
                    'relationship_type' => Arr::get($representative, 'relationship_type'),
                    'description' => Arr::get($representative, 'description'),
                    'is_billable' => filter_var(Arr::get($representative, 'is_billable', false), FILTER_VALIDATE_BOOLEAN),
                    'is_emergency_contact' => filter_var(Arr::get($representative, 'is_emergency_contact', false), FILTER_VALIDATE_BOOLEAN),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @throws ValidationException
     */
    protected function resolveOrCreateLegalRepresentative(array $representative, string $tenantId): LegalRepresentative {
        $legalRepresentativeData = Arr::get($representative, 'legal_representative', []);

        if ($legalRepresentativeId = Arr::get($representative, 'legal_representative_id')) {
            $legalRepresentative = LegalRepresentative::query()
                ->where('tenant_id', $tenantId)
                ->where('id', $legalRepresentativeId)
                ->first();

            if (! $legalRepresentative) {
                throw ValidationException::withMessages([
                    'representatives' => __('messages.legal_representatives.not_found'),
                ]);
            }

            if (! empty($legalRepresentativeData)) {
                $legalRepresentative->fill($this->extractLegalRepresentativePayload($legalRepresentativeData));
                $legalRepresentative->save();

                if ($legalRepresentative->person && Arr::has($legalRepresentativeData, 'person')) {
                    $this->updatePersonIfNeeded(
                        $legalRepresentative->person,
                        Arr::get($legalRepresentativeData, 'person', [])
                    );
                }
            }

            return $legalRepresentative;
        }

        $person = $this->resolveOrCreatePerson($legalRepresentativeData, $tenantId, 'representative');

        $legalRepresentative = LegalRepresentative::query()
            ->where('tenant_id', $tenantId)
            ->where('person_id', $person->id)
            ->first();

        if ($legalRepresentative) {
            $legalRepresentative->fill($this->extractLegalRepresentativePayload($legalRepresentativeData));
            $legalRepresentative->save();

            return $legalRepresentative;
        }

        return LegalRepresentative::query()->create([
            'tenant_id' => $tenantId,
            'person_id' => $person->id,
            ...$this->extractLegalRepresentativePayload($legalRepresentativeData),
        ]);
    }

    /**
     * @throws ValidationException
     */
    protected function resolveOrCreatePerson(array $ownerData, string $tenantId, string $errorPrefix): Person {
        if ($personId = Arr::get($ownerData, 'person_id')) {
            $person = Person::query()
                ->where('id', $personId)
                ->first();

            if (! $person) {
                throw ValidationException::withMessages([
                    "{$errorPrefix}.person_id" => __('messages.persons.lookup_not_found'),
                ]);
            }

            $this->updatePersonIfNeeded($person, Arr::get($ownerData, 'person', []));

            return $person;
        }

        $personData = Arr::get($ownerData, 'person', []);
        $personPayload = $this->extractPersonPayload($personData);

        $legalId = trim((string) Arr::get($personPayload, 'legal_id', ''));
        $legalIdType = trim((string) Arr::get($personPayload, 'legal_id_type', ''));

        $person = null;

        if ($legalId !== '') {
            $person = Person::query()
                ->where('legal_id', $legalId)
                ->when($legalIdType !== '', fn ($query) => $query->where('legal_id_type', $legalIdType))
                ->first();
        }

        $photo = Arr::get($personData, 'photo');

        if ($photo instanceof UploadedFile) {
            $personPayload['photo'] = $person
                ? $this->replacePhoto($person->photo, $photo, $personPayload['legal_id'])
                : $this->storePhoto($photo, $personPayload['legal_id']);
        } elseif ($person) {
            $newLegalId = (string) Arr::get($personPayload, 'legal_id', $person->legal_id);

            if ($newLegalId !== $person->legal_id && $person->photo) {
                $personPayload['photo'] = $this->renamePhoto($person->photo, $newLegalId);
            }
        }

        if ($person) {
            $person->fill($personPayload);
            $person->save();

            return $person;
        }

        return Person::query()->create($personPayload);
    }

    protected function updatePersonIfNeeded(Person $person, array $personData): void
    {
        if (empty($personData)) {
            return;
        }

        $payload = $this->extractPersonPayload($personData);
        $photo = Arr::get($personData, 'photo');
        $newLegalId = (string) Arr::get($payload, 'legal_id', $person->legal_id);

        if ($photo instanceof UploadedFile) {
            $payload['photo'] = $this->replacePhoto($person->photo, $photo, $newLegalId);
        } elseif ($newLegalId !== $person->legal_id && $person->photo) {
            $payload['photo'] = $this->renamePhoto($person->photo, $newLegalId);
        }

        $person->fill($payload);

        if ($person->isDirty()) {
            $person->save();
        }
    }

    protected function extractStudentPayload(array $data): array
    {
        return Arr::only($data, [
            'status',
            'notes',
        ]);
    }

    protected function extractLegalRepresentativePayload(array $data): array
    {
        return Arr::only($data, [
            'status',
            'notes',
        ]);
    }

    protected function extractPersonPayload(array $data): array
    {
        return Arr::only($data, [
            'full_name',
            'photo',
            'email',
            'phone',
            'address',
            'country_id',
            'state_id',
            'city_id',
            'zip',
            'legal_id',
            'legal_id_type',
            'birthday',
            'gender',
            'marital_status',
            'blood_group',
            'nationality',
            'deceased_at',
            'status_changed_at',
        ]);
    }

    /**
     * @throws ValidationException
     */
    protected function generateStudentCode(string $tenantId): string
    {
        $prefix = 'STU';

        for ($attempt = 0; $attempt < 30; $attempt++) {
            $code = sprintf(
                '%s-%s-%s',
                $prefix,
                now()->format('ym'),
                strtoupper(Str::random(10))
            );

            $exists = Student::query()
                ->where('tenant_id', $tenantId)
                ->where('student_code', $code)
                ->exists();

            if (! $exists) {
                return $code;
            }
        }

        throw ValidationException::withMessages([
            'student_code' => __('messages.students.code_generation_failed'),
        ]);
    }

    /**
     * @throws ValidationException
     */
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

    /**
     * @throws ValidationException
     */
    protected function ensureEnrollmentDoesNotExist(array $data, string $tenantId): void
    {
        $query = Enrollment::query()
            ->where('tenant_id', $tenantId)
            ->where('student_id', Arr::get($data, 'student_id'))
            ->where('academic_year_id', Arr::get($data, 'academic_year_id'));

        $this->whereNullable($query, 'course_id', Arr::get($data, 'course_id'));
        $this->whereNullable($query, 'specialty_id', Arr::get($data, 'specialty_id'));
        $this->whereNullable($query, 'parallel_id', Arr::get($data, 'parallel_id'));
        $this->whereNullable($query, 'shift_id', Arr::get($data, 'shift_id'));

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'student_id' => __('messages.enrollments.already_exists'),
            ]);
        }
    }

    /**
     * @throws ValidationException
     */
    protected function ensureEnrollmentDoesNotExistForUpdate(Enrollment $enrollment, array $data, string $tenantId): void {
        $studentId = Arr::get($data, 'student_id', $enrollment->student_id);
        $academicYearId = Arr::get($data, 'academic_year_id', $enrollment->academic_year_id);
        $courseId = Arr::get($data, 'course_id', $enrollment->course_id);
        $specialtyId = Arr::get($data, 'specialty_id', $enrollment->specialty_id);
        $parallelId = Arr::get($data, 'parallel_id', $enrollment->parallel_id);
        $shiftId = Arr::get($data, 'shift_id', $enrollment->shift_id);

        $query = Enrollment::query()
            ->where('tenant_id', $tenantId)
            ->where('id', '!=', $enrollment->id)
            ->where('student_id', $studentId)
            ->where('academic_year_id', $academicYearId);

        $this->whereNullable($query, 'course_id', $courseId);
        $this->whereNullable($query, 'specialty_id', $specialtyId);
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

    protected function createStudentUserLink(Enrollment $enrollment, StudentLegalRepresentative $relationship): ?StudentUserLink {
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

    protected function storePhoto(UploadedFile $photo, string $legalId): string
    {
        $extension = strtolower($photo->getClientOriginalExtension() ?: $photo->extension() ?: 'jpg');
        $filename = "{$legalId}.{$extension}";
        $path = "{$this->directory}/{$filename}";

        $this->deleteMatchingPhotoVariants($legalId);

        Storage::disk($this->disk)->putFileAs(
            $this->directory,
            $photo,
            $filename
        );

        return $path;
    }

    protected function replacePhoto(?string $currentPath, UploadedFile $photo, string $legalId): string
    {
        $this->deletePhoto($currentPath);

        return $this->storePhoto($photo, $legalId);
    }

    protected function renamePhoto(string $currentPath, string $newLegalId): string
    {
        $extension = pathinfo($currentPath, PATHINFO_EXTENSION) ?: 'jpg';
        $newPath = "{$this->directory}/{$newLegalId}.{$extension}";

        $this->deleteMatchingPhotoVariants($newLegalId);

        if (Storage::disk($this->disk)->exists($currentPath)) {
            Storage::disk($this->disk)->move($currentPath, $newPath);
        }

        return $newPath;
    }

    protected function deletePhoto(?string $path): void
    {
        if ($path && Storage::disk($this->disk)->exists($path)) {
            Storage::disk($this->disk)->delete($path);
        }
    }

    protected function deleteMatchingPhotoVariants(string $legalId): void
    {
        foreach (['jpg', 'jpeg', 'png', 'webp'] as $extension) {
            $path = "{$this->directory}/{$legalId}.{$extension}";

            if (Storage::disk($this->disk)->exists($path)) {
                Storage::disk($this->disk)->delete($path);
            }
        }
    }

    protected function formatPreparedEnrollment(?Enrollment $enrollment): array
    {
        if (! $enrollment) {
            return [
                'source' => null,
                'found' => [
                    'person' => false,
                    'student' => false,
                    'legal_representative' => false,
                    'previous_enrollment' => false,
                    'external_person' => false,
                ],
                'person' => null,
                'student' => null,
                'legal_representative' => null,
                'last_enrollment' => null,
                'representatives' => [],
                'promotion_suggestion' => null,
                'suggested_course' => null,
            ];
        }

        $student = $enrollment->student;
        $person = $student?->person;

        return [
            'source' => 'database',
            'found' => [
                'person' => (bool) $person,
                'student' => (bool) $student,
                'legal_representative' => false,
                'previous_enrollment' => true,
                'external_person' => false,
            ],
            'person' => $person,
            'student' => $student,
            'legal_representative' => null,
            'last_enrollment' => $enrollment,
            'representatives' => $this->formatEnrollmentRepresentatives($enrollment),
            'promotion_suggestion' => null,
            'suggested_course' => null,
        ];
    }

    protected function formatEnrollmentRepresentatives(Enrollment $enrollment): array
    {
        return $enrollment->student?->legalRepresentativeRelationships
            ?->map(function ($relationship) {
                return [
                    'id' => $relationship->id,
                    'legal_representative_id' => $relationship->legal_representative_id,
                    'relationship_type' => $relationship->relationship_type,
                    'description' => $relationship->description,
                    'is_billable' => (bool) $relationship->is_billable,
                    'is_emergency_contact' => (bool) $relationship->is_emergency_contact,
                    'legal_representative' => $relationship->legalRepresentative,
                ];
            })
            ->values()
            ->all() ?? [];
    }
}
