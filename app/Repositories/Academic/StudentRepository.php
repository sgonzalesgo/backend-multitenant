<?php

namespace App\Repositories\Academic;

use App\Models\Academic\Student;
use App\Models\Administration\Tenant;
use App\Models\Administration\User;
use App\Models\General\Person;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class StudentRepository
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

    public function list(array $filters = []): LengthAwarePaginator
    {
        $tenantId = $this->resolveCurrentTenantId();

        if (! $tenantId) {
            throw ValidationException::withMessages([
                'tenant' => __('messages.students.tenant_not_resolved'),
            ]);
        }

        $rawQ = Arr::get($filters, 'q', '');
        $sort = Arr::get($filters, 'sort', 'created_at');
        $dir = strtolower((string) Arr::get($filters, 'dir', 'desc')) === 'asc' ? 'asc' : 'desc';
        $perPage = max(1, min((int) Arr::get($filters, 'per_page', 15), 100));

        if (! in_array($sort, [
            'student_code',
            'status',
            'created_at',
            'updated_at',
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

        $fullName = trim((string) Arr::get($columns, 'full_name', ''));
        $email = trim((string) Arr::get($columns, 'email', ''));
        $legalId = trim((string) Arr::get($columns, 'legal_id', ''));
        $studentCode = trim((string) Arr::get($columns, 'student_code', ''));
        $status = trim((string) Arr::get($columns, 'status', ''));

        return Student::query()
            ->with($this->relations())
            ->where('tenant_id', $tenantId)
            ->when($global !== '', function ($query) use ($global) {
                $query->where(function ($q) use ($global) {
                    $q->where('student_code', 'ilike', "%{$global}%")
                        ->orWhere('status', 'ilike', "%{$global}%")
                        ->orWhereHas('person', function ($personQuery) use ($global) {
                            $personQuery->where('full_name', 'ilike', "%{$global}%")
                                ->orWhere('email', 'ilike', "%{$global}%")
                                ->orWhere('phone', 'ilike', "%{$global}%")
                                ->orWhere('legal_id', 'ilike', "%{$global}%");
                        });
                });
            })
            ->when($fullName !== '', function ($query) use ($fullName) {
                $query->whereHas('person', fn ($q) => $q->where('full_name', 'ilike', "%{$fullName}%"));
            })
            ->when($email !== '', function ($query) use ($email) {
                $query->whereHas('person', fn ($q) => $q->where('email', 'ilike', "%{$email}%"));
            })
            ->when($legalId !== '', function ($query) use ($legalId) {
                $query->whereHas('person', fn ($q) => $q->where('legal_id', 'ilike', "%{$legalId}%"));
            })
            ->when($studentCode !== '', fn ($query) => $query->where('student_code', 'ilike', "%{$studentCode}%"))
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->orderBy($sort, $dir)
            ->paginate($perPage);
    }

    public function active(array $filters = []): LengthAwarePaginator
    {
        $tenantId = $this->resolveCurrentTenantId();

        if (! $tenantId) {
            throw ValidationException::withMessages([
                'tenant' => __('messages.students.tenant_not_resolved'),
            ]);
        }

        $perPage = max(1, min((int) Arr::get($filters, 'per_page', 15), 100));

        return Student::query()
            ->with($this->relations())
            ->where('tenant_id', $tenantId)
            ->where('status', 'active')
            ->whereHas('person', function ($query) {
                $query->whereNull('deleted_at')
                    ->whereNull('deceased_at');
            })
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    public function find(Student $student): Student
    {
        $tenantId = $this->resolveCurrentTenantId();

        if ($tenantId && (string) $student->tenant_id !== $tenantId) {
            abort(404);
        }

        return $student->load($this->relations());
    }

    public function create(array $data, ?UploadedFile $photo = null): Student
    {
        $tenantId = $this->resolveCurrentTenantId();

        if (! $tenantId) {
            throw ValidationException::withMessages([
                'tenant' => __('messages.students.tenant_not_resolved'),
            ]);
        }

        return DB::transaction(function () use ($data, $photo, $tenantId) {
            $personPayload = $this->extractPersonPayload($data);

            $person = Person::query()
                ->where('legal_id', $personPayload['legal_id'])
                ->where('legal_id_type', $personPayload['legal_id_type'])
                ->first();

            if ($photo) {
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
            } else {
                $person = Person::query()->create($personPayload);
            }

            $this->syncUser($person, $data, ! $person->wasRecentlyCreated);

            $existingStudent = Student::query()
                ->where('tenant_id', $tenantId)
                ->where('person_id', $person->id)
                ->first();

            if ($existingStudent) {
                throw ValidationException::withMessages([
                    'legal_id' => __('messages.students.already_exists'),
                ]);
            }

            $student = Student::query()->create([
                'tenant_id' => $tenantId,
                'person_id' => $person->id,
                'student_code' => $this->generateStudentCode($tenantId),
                ...$this->extractStudentPayload($data),
            ]);

            return $this->find($student->refresh());
        });
    }

    public function update(Student $student, array $data, ?UploadedFile $photo = null): Student
    {
        $tenantId = $this->resolveCurrentTenantId();

        if ($tenantId && (string) $student->tenant_id !== $tenantId) {
            abort(404);
        }

        return DB::transaction(function () use ($student, $data, $photo) {
            $person = $student->person;
            $personPayload = $this->extractPersonPayload($data);

            $newLegalId = (string) Arr::get($personPayload, 'legal_id', $person->legal_id);

            if ($photo) {
                $personPayload['photo'] = $this->replacePhoto($person->photo, $photo, $newLegalId);
            } elseif ($newLegalId !== $person->legal_id && $person->photo) {
                $personPayload['photo'] = $this->renamePhoto($person->photo, $newLegalId);
            }

            $person->fill($personPayload);
            $person->save();

            $this->syncUser($person, $data, false);

            $student->fill($this->extractStudentPayload($data));
            $student->save();

            return $this->find($student->refresh());
        });
    }

    public function delete(Student $student): void
    {
        $tenantId = $this->resolveCurrentTenantId();

        if ($tenantId && (string) $student->tenant_id !== $tenantId) {
            abort(404);
        }

        DB::transaction(function () use ($student) {
            $student->delete();

            // No se elimina la persona porque puede pertenecer a otros roles:
            // instructor, representante, empleado, usuario, etc.
        });
    }

    protected function relations(): array
    {
        return [
            'tenant:id,name',
            'person.user:id,person_id,name,email,avatar,status',
            'person.country:id,code,name',
            'person.state:id,country_id,code,name',
            'person.city:id,state_id,name',
        ];
    }

    protected function extractStudentPayload(array $data): array
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

    protected function syncUser(Person $person, array $data, bool $isCreate): void
    {
        $hasUser = filter_var(Arr::get($data, 'has_user', false), FILTER_VALIDATE_BOOLEAN);

        if (! $hasUser) {
            return;
        }

        $user = $person->user;

        if (! $user) {
            $user = new User();
            $user->person_id = $person->id;
        }

        $user->name = (string) Arr::get($data, 'user_name', $person->full_name);
        $user->email = (string) Arr::get($data, 'user_email', $person->email);
        $user->status = (string) Arr::get($data, 'user_status', $user->status ?? 'active');

        $password = Arr::get($data, 'user_password');

        if ($isCreate || filled($password)) {
            $user->password = Hash::make((string) $password);
        }

        $user->save();
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
}
