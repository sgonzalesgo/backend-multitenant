<?php

namespace App\Repositories\Academic;

use App\Models\Academic\Instructor;
use App\Models\Administration\Tenant;
use App\Models\General\Person;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class InstructorRepository
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
            abort(400, __('messages.instructors.tenant_not_resolved'));
        }

        $rawQ = trim((string) Arr::get($filters, 'q', ''));
        $sort = Arr::get($filters, 'sort', 'created_at');
        $dir = strtolower((string) Arr::get($filters, 'dir', 'desc')) === 'asc' ? 'asc' : 'desc';
        $perPage = max(1, min((int) Arr::get($filters, 'per_page', 15), 100));

        if (! in_array($sort, [
            'code',
            'academic_title',
            'academic_level',
            'specialty',
            'status',
            'created_at',
            'updated_at',
        ], true)) {
            $sort = 'created_at';
        }

        $global = '';
        $code = '';
        $status = '';
        $fullName = '';
        $legalId = '';
        $createdAt = '';

        if ($rawQ !== '') {
            $decoded = json_decode($rawQ, true);

            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $global = trim((string) Arr::get($decoded, 'global', ''));
                $code = trim((string) Arr::get($decoded, 'columns.code', ''));
                $status = trim((string) Arr::get($decoded, 'columns.status', ''));
                $fullName = trim((string) Arr::get($decoded, 'columns.full_name', ''));
                $legalId = trim((string) Arr::get($decoded, 'columns.legal_id', ''));
                $createdAt = trim((string) Arr::get($decoded, 'columns.created_at', ''));
            } else {
                $global = $rawQ;
            }
        }

        return Instructor::query()
            ->with(['person', 'tenant:id,name'])
            ->where('tenant_id', $tenantId)
            ->when($global !== '', function ($query) use ($global) {
                $query->where(function ($q) use ($global) {
                    $q->where('code', 'ilike', "%{$global}%")
                        ->orWhere('academic_title', 'ilike', "%{$global}%")
                        ->orWhere('academic_level', 'ilike', "%{$global}%")
                        ->orWhere('specialty', 'ilike', "%{$global}%")
                        ->orWhereHas('person', function ($personQuery) use ($global) {
                            $personQuery->where('full_name', 'ilike', "%{$global}%")
                                ->orWhere('legal_id', 'ilike', "%{$global}%")
                                ->orWhere('email', 'ilike', "%{$global}%")
                                ->orWhere('phone', 'ilike', "%{$global}%");
                        });
                });
            })
            ->when($code !== '', fn ($query) => $query->where('code', 'ilike', "%{$code}%"))
            ->when($status !== '', fn ($query) => $query->where('status', 'ilike', "%{$status}%"))
            ->when($fullName !== '', function ($query) use ($fullName) {
                $query->whereHas('person', fn ($personQuery) => $personQuery->where('full_name', 'ilike', "%{$fullName}%"));
            })
            ->when($legalId !== '', function ($query) use ($legalId) {
                $query->whereHas('person', fn ($personQuery) => $personQuery->where('legal_id', 'ilike', "%{$legalId}%"));
            })
            ->when($createdAt !== '', fn ($query) => $query->whereDate('created_at', $createdAt))
            ->orderBy($sort, $dir)
            ->paginate($perPage);
    }

    public function create(array $data, ?UploadedFile $photo = null): Instructor
    {
        $tenantId = $this->resolveCurrentTenantId();
        $tenant = Tenant::query()->findOrFail($tenantId);

        if (! $tenantId) {
            abort(400, __('messages.instructors.tenant_not_resolved'));
        }

        return DB::transaction(function () use ($tenant, $data, $photo, $tenantId) {
            $person = Person::query()
                ->where('legal_id', $data['legal_id'])
                ->first();

            if ($person) {
                $this->fillPerson($person, $data, $photo);
            } else {
                $person = new Person();
                $this->fillPerson($person, $data, $photo);
            }

            $alreadyExists = Instructor::query()
                ->where('tenant_id', $tenantId)
                ->where('person_id', $person->id)
                ->exists();

            if ($alreadyExists) {
                abort(422, __('validation/academic/instructor.custom.person_id.unique_in_tenant'));
            }

            $instructor = Instructor::query()->create([
                'person_id' => $person->id,
                'tenant_id' => $tenantId,
                'code' => $this->generateInstructorCode($tenant->name),
                'academic_title' => Arr::get($data, 'academic_title'),
                'academic_level' => Arr::get($data, 'academic_level'),
                'specialty' => Arr::get($data, 'specialty'),
                'status' => Arr::get($data, 'status', 'active'),
                'status_changed_at' => Arr::get($data, 'status_changed_at'),
            ]);

            return $instructor->load(['person', 'tenant:id,name']);
        });
    }

    public function update(Instructor $instructor, array $data, ?UploadedFile $photo = null): Instructor
    {
        $tenantId = $this->resolveCurrentTenantId();

        if (! $tenantId) {
            abort(400, __('messages.instructors.tenant_not_resolved'));
        }

        if ((string) $instructor->tenant_id !== (string) $tenantId) {
            abort(404);
        }

        return DB::transaction(function () use ($instructor, $data, $photo, $tenantId) {
            $person = $instructor->person;
            $newLegalId = (string) Arr::get($data, 'legal_id', $person->legal_id);

            $anotherPerson = Person::query()
                ->where('legal_id', $newLegalId)
                ->where('id', '!=', $person->id)
                ->first();

            if ($anotherPerson) {
                $duplicateInstructor = Instructor::query()
                    ->where('tenant_id', $tenantId)
                    ->where('person_id', $anotherPerson->id)
                    ->where('id', '!=', $instructor->id)
                    ->exists();

                if ($duplicateInstructor) {
                    abort(422, __('validation/academic/instructor.custom.person_id.unique_in_tenant'));
                }
            }

            $this->fillPerson($person, $data, $photo);

            $instructor->fill([
                'academic_title' => Arr::get($data, 'academic_title', $instructor->academic_title),
                'academic_level' => Arr::get($data, 'academic_level', $instructor->academic_level),
                'specialty' => Arr::get($data, 'specialty', $instructor->specialty),
                'status' => Arr::get($data, 'status', $instructor->status),
                'status_changed_at' => Arr::get($data, 'status_changed_at', $instructor->status_changed_at),
            ]);

            $instructor->save();

            return $instructor->refresh()->load(['person', 'tenant:id,name']);
        });
    }

    public function delete(Instructor $instructor): void
    {
        $tenantId = $this->resolveCurrentTenantId();

        if (! $tenantId) {
            abort(400, __('messages.instructors.tenant_not_resolved'));
        }

        if ((string) $instructor->tenant_id !== (string) $tenantId) {
            abort(404);
        }

        $instructor->status='inactive';
        $instructor->save();

        $instructor->delete();
    }

    protected function fillPerson(Person $person, array $data, ?UploadedFile $photo = null): void
    {
        $person->fill([
            'full_name' => Arr::get($data, 'full_name', $person->full_name),
            'email' => Arr::get($data, 'email', $person->email),
            'phone' => Arr::get($data, 'phone', $person->phone),
            'address' => Arr::get($data, 'address', $person->address),
            'city' => Arr::get($data, 'city', $person->city),
            'state' => Arr::get($data, 'state', $person->state),
            'country' => Arr::get($data, 'country', $person->country),
            'zip' => Arr::get($data, 'zip', $person->zip),
            'legal_id' => Arr::get($data, 'legal_id', $person->legal_id),
            'legal_id_type' => Arr::get($data, 'legal_id_type', $person->legal_id_type),
            'birthday' => Arr::get($data, 'birthday', $person->birthday),
            'gender' => Arr::get($data, 'gender', $person->gender),
            'marital_status' => Arr::get($data, 'marital_status', $person->marital_status),
            'blood_group' => Arr::get($data, 'blood_group', $person->blood_group),
            'nationality' => Arr::get($data, 'nationality', $person->nationality),
            'status' => Arr::get($data, 'person_status', $person->status ?? 'active'),
        ]);

        $person->save();

        $finalLegalId = (string) $person->legal_id;

        if ($photo) {
            $person->photo = $this->replacePhoto($person->photo, $photo, $finalLegalId);
            $person->save();
        } elseif ($person->wasChanged('legal_id') && $person->photo) {
            $person->photo = $this->renamePhoto($person->photo, $finalLegalId);
            $person->save();
        }
    }

    protected function replacePhoto(?string $currentPath, UploadedFile $photo, string $legalId): string
    {
        $this->deletePhoto($currentPath);

        return $this->storePhoto($photo, $legalId);
    }

    protected function storePhoto(UploadedFile $photo, string $legalId): string
    {
        $extension = strtolower($photo->getClientOriginalExtension() ?: $photo->extension() ?: 'jpg');
        $filename = "{$legalId}.{$extension}";
        $path = "{$this->directory}/{$filename}";

        $this->deleteMatchingPhotoVariants($legalId);

        Storage::disk($this->disk)->putFileAs($this->directory, $photo, $filename);

        return $path;
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

    protected function generateInstructorCode(string $tenantName): string
    {
        $prefix = $this->tenantInitials($tenantName);
        $prefix = substr($prefix, 0, 4);

        $maxLength = 10;
        $randomLength = max(1, $maxLength - strlen($prefix));

        for ($i = 0; $i < 20; $i++) {
            $random = $this->randomAlphaNumeric($randomLength);
            $code = substr($prefix . $random, 0, $maxLength);

            $exists = Instructor::query()
                ->where('code', $code)
                ->exists();

            if (! $exists) {
                return $code;
            }
        }

        throw new \RuntimeException(__('messages.instructors.code_generation_failed'));
    }

    protected function tenantInitials(string $tenantName): string
    {
        $words = preg_split('/\s+/', trim($tenantName)) ?: [];

        $initials = collect($words)
            ->filter()
            ->map(fn (string $word) => mb_substr($word, 0, 1))
            ->implode('');

        $initials = strtoupper(preg_replace('/[^A-Za-z]/', '', $initials) ?: 'INS');

        return $initials !== '' ? $initials : 'INS';
    }

    protected function randomAlphaNumeric(int $length): string
    {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $result = '';

        for ($i = 0; $i < $length; $i++) {
            $result .= $chars[random_int(0, strlen($chars) - 1)];
        }

        return $result;
    }
}
