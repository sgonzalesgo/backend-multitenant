<?php

namespace App\Repositories\Academic;

use App\Models\Academic\LegalRepresentative;
use App\Models\Academic\StudentLegalRepresentative;
use App\Models\Administration\Tenant;
use App\Models\Administration\User;
use App\Models\General\Person;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class LegalRepresentativeRepository
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
                'tenant' => __('messages.legal_representatives.tenant_not_resolved'),
            ]);
        }

        $rawQ = Arr::get($filters, 'q', '');
        $sort = Arr::get($filters, 'sort', 'created_at');
        $dir = strtolower((string) Arr::get($filters, 'dir', 'desc')) === 'asc' ? 'asc' : 'desc';
        $perPage = max(1, min((int) Arr::get($filters, 'per_page', 15), 100));

        if (! in_array($sort, [
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
        $status = trim((string) Arr::get($columns, 'status', ''));

        return LegalRepresentative::query()
            ->with($this->relations())
            ->where('tenant_id', $tenantId)
            ->when($global !== '', function ($query) use ($global) {
                $query->where(function ($q) use ($global) {
                    $q->where('status', 'ilike', "%{$global}%")
                        ->orWhereHas('person', function ($personQuery) use ($global) {
                            $personQuery->where('full_name', 'ilike', "%{$global}%")
                                ->orWhere('email', 'ilike', "%{$global}%")
                                ->orWhere('phone', 'ilike', "%{$global}%")
                                ->orWhere('legal_id', 'ilike', "%{$global}%");
                        })
                        ->orWhereHas('studentRelationships.student.person', function ($studentPersonQuery) use ($global) {
                            $studentPersonQuery->where('full_name', 'ilike', "%{$global}%")
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
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->orderBy($sort, $dir)
            ->paginate($perPage);
    }

    /**
     * @throws ValidationException
     */
    public function active(array $filters = []): LengthAwarePaginator
    {
        $tenantId = $this->resolveCurrentTenantId();

        if (! $tenantId) {
            throw ValidationException::withMessages([
                'tenant' => __('messages.legal_representatives.tenant_not_resolved'),
            ]);
        }

        $perPage = max(1, min((int) Arr::get($filters, 'per_page', 15), 100));

        return LegalRepresentative::query()
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

    public function find(LegalRepresentative $legalRepresentative): LegalRepresentative
    {
        $tenantId = $this->resolveCurrentTenantId();

        if ($tenantId && (string) $legalRepresentative->tenant_id !== $tenantId) {
            abort(404);
        }

        return $legalRepresentative->load($this->relations());
    }

    public function create(array $data, ?UploadedFile $photo = null): LegalRepresentative
    {
        $tenantId = $this->resolveCurrentTenantId();

        if (! $tenantId) {
            throw ValidationException::withMessages([
                'tenant' => __('messages.legal_representatives.tenant_not_resolved'),
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

            $existingRepresentative = LegalRepresentative::query()
                ->where('tenant_id', $tenantId)
                ->where('person_id', $person->id)
                ->first();

            if ($existingRepresentative) {
                throw ValidationException::withMessages([
                    'legal_id' => __('messages.legal_representatives.already_exists'),
                ]);
            }

            $legalRepresentative = LegalRepresentative::query()->create([
                'tenant_id' => $tenantId,
                'person_id' => $person->id,
                ...$this->extractLegalRepresentativePayload($data),
            ]);

            $this->syncStudentRelationships($legalRepresentative, $data, $tenantId);

            return $this->find($legalRepresentative->refresh());
        });
    }

    public function update(LegalRepresentative $legalRepresentative, array $data, ?UploadedFile $photo = null): LegalRepresentative
    {
        $tenantId = $this->resolveCurrentTenantId();

        if ($tenantId && (string) $legalRepresentative->tenant_id !== $tenantId) {
            abort(404);
        }

        return DB::transaction(function () use ($legalRepresentative, $data, $photo, $tenantId) {
            $person = $legalRepresentative->person;
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

            $legalRepresentative->fill($this->extractLegalRepresentativePayload($data));
            $legalRepresentative->save();

            if (array_key_exists('students', $data)) {
                $this->syncStudentRelationships($legalRepresentative, $data, (string) $tenantId);
            }

            return $this->find($legalRepresentative->refresh());
        });
    }

    public function delete(LegalRepresentative $legalRepresentative): void
    {
        $tenantId = $this->resolveCurrentTenantId();

        if ($tenantId && (string) $legalRepresentative->tenant_id !== $tenantId) {
            abort(404);
        }

        DB::transaction(function () use ($legalRepresentative) {
            $legalRepresentative->studentRelationships()->delete();
            $legalRepresentative->delete();

            // No se elimina la persona porque puede pertenecer a otros roles:
            // estudiante, instructor, empleado, usuario, etc.
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
            'studentRelationships.student:id,tenant_id,person_id,student_code,status',
            'studentRelationships.student.person:id,full_name,email,phone,legal_id,legal_id_type,photo',
        ];
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

    protected function syncStudentRelationships(
        LegalRepresentative $legalRepresentative,
        array $data,
        string $tenantId
    ): void {
        $students = Arr::get($data, 'students', []);

        if (! is_array($students)) {
            return;
        }

        StudentLegalRepresentative::withTrashed()
            ->where('tenant_id', $tenantId)
            ->where('legal_representative_id', $legalRepresentative->id)
            ->forceDelete();

        foreach ($students as $student) {
            $studentId = Arr::get($student, 'student_id');
            $relationshipType = Arr::get($student, 'relationship_type');

            if (! $studentId || ! $relationshipType) {
                continue;
            }

            StudentLegalRepresentative::query()->create([
                'tenant_id' => $tenantId,
                'student_id' => $studentId,
                'legal_representative_id' => $legalRepresentative->id,
                'relationship_type' => $relationshipType,
                'description' => Arr::get($student, 'description'),
                'is_billable' => filter_var(Arr::get($student, 'is_billable', false), FILTER_VALIDATE_BOOLEAN),
                'is_emergency_contact' => filter_var(Arr::get($student, 'is_emergency_contact', false), FILTER_VALIDATE_BOOLEAN),
            ]);
        }
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
