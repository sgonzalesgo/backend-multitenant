<?php

namespace App\Repositories\Academic;

use App\Models\Academic\Instructor;
use App\Models\General\Person;
use App\Models\Administration\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class InstructorRepository
{
    protected string $disk = 'public';

    protected string $directory = 'persons';

    public function list(array $filters = []): LengthAwarePaginator
    {
        $rawQ = Arr::get($filters, 'q', '');
        $sort = Arr::get($filters, 'sort', 'created_at');
        $dir = strtolower((string) Arr::get($filters, 'dir', 'desc')) === 'asc' ? 'asc' : 'desc';
        $perPage = max(1, min((int) Arr::get($filters, 'per_page', 15), 100));

        if (! in_array($sort, [
            'academic_title',
            'academic_level',
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
        $departmentId = trim((string) Arr::get($columns, 'department_id', ''));
        $academicTitle = trim((string) Arr::get($columns, 'academic_title', ''));
        $academicLevel = trim((string) Arr::get($columns, 'academic_level', ''));
        $status = trim((string) Arr::get($columns, 'status', ''));

        return Instructor::query()
            ->with($this->relations())
            ->when($global !== '', function ($query) use ($global) {
                $query->where(function ($q) use ($global) {
                    $q->where('academic_title', 'ilike', "%{$global}%")
                        ->orWhere('academic_level', 'ilike', "%{$global}%")
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
            ->when($departmentId !== '', fn ($query) => $query->where('department_id', $departmentId))
            ->when($academicTitle !== '', fn ($query) => $query->where('academic_title', 'ilike', "%{$academicTitle}%"))
            ->when($academicLevel !== '', fn ($query) => $query->where('academic_level', 'ilike', "%{$academicLevel}%"))
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->orderBy($sort, $dir)
            ->paginate($perPage);
    }

    public function active(array $filters = []): LengthAwarePaginator
    {
        return Instructor::query()
            ->with($this->relations())
            ->where('status', 'active')
            ->whereHas('person', function ($query) {
                $query->whereNull('deleted_at')
                    ->whereNull('deceased_at');
            })
            ->orderBy('created_at', 'desc')
            ->paginate(
                max(1, min((int) ($filters['per_page'] ?? 15), 100))
            );
    }

    public function find(Instructor $instructor): Instructor
    {
        return $instructor->load($this->relations());
    }

    public function create(array $data, ?UploadedFile $photo = null): Instructor
    {
        return DB::transaction(function () use ($data, $photo) {
            $personPayload = $this->extractPersonPayload($data);

            if ($photo) {
                $personPayload['photo'] = $this->storePhoto($photo, $personPayload['legal_id']);
            }

            $person = Person::query()->create($personPayload);

            $this->syncUser($person, $data, true);

            $instructor = Instructor::query()->create([
                'person_id' => $person->id,
                ...$this->extractInstructorPayload($data),
            ]);

            return $this->find($instructor->refresh());
        });
    }

    public function update(Instructor $instructor, array $data, ?UploadedFile $photo = null): Instructor
    {
        return DB::transaction(function () use ($instructor, $data, $photo) {
            $person = $instructor->person;

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

            $instructor->fill($this->extractInstructorPayload($data));
            $instructor->save();

            return $this->find($instructor->refresh());
        });
    }

    public function delete(Instructor $instructor): void
    {
        DB::transaction(function () use ($instructor) {
            $instructor->delete();

            // OJO:
            // No elimino la persona porque puede servir para otros módulos:
            // student, representative, employee, user, etc.
        });
    }

    protected function relations(): array
    {
        return [
            'person.user:id,person_id,name,email,avatar,status',
            'person.country:id,code,name',
            'person.state:id,country_id,code,name',
            'person.city:id,state_id,name',
            'department:id,code,name',
        ];
    }

    protected function extractInstructorPayload(array $data): array
    {
        return Arr::only($data, [
            'department_id',
            'academic_title',
            'academic_level',
            'status',
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
