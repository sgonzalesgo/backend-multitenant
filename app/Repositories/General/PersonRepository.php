<?php

namespace App\Repositories\General;

use App\Models\Administration\Tenant;
use App\Models\Administration\User;
use App\Models\General\Person;
use App\Repositories\Administration\AuditLogRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class PersonRepository
{
    protected string $disk = 'public';
    protected string $directory = 'persons';

    public function __construct(
        protected ?AuditLogRepository $audit = null
    ) {
    }

    protected function audit(): AuditLogRepository
    {
        return $this->audit ??= app(AuditLogRepository::class);
    }

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
        $rawQ = trim((string) Arr::get($filters, 'q', ''));
        $sort = Arr::get($filters, 'sort', 'full_name');
        $dir = strtolower((string) Arr::get($filters, 'dir', 'asc')) === 'desc' ? 'desc' : 'asc';
        $perPage = max(1, min((int) Arr::get($filters, 'per_page', 15), 100));

        if (! in_array($sort, [
            'full_name',
            'email',
            'phone',
            'legal_id',
            'created_at',
            'updated_at',
        ], true)) {
            $sort = 'full_name';
        }

        $global = '';
        $fullName = '';
        $email = '';
        $phone = '';
        $legalId = '';
        $createdAt = '';

        if ($rawQ !== '') {
            $decoded = json_decode($rawQ, true);

            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $global = trim((string) Arr::get($decoded, 'global', ''));
                $fullName = trim((string) Arr::get($decoded, 'columns.full_name', ''));
                $email = trim((string) Arr::get($decoded, 'columns.email', ''));
                $phone = trim((string) Arr::get($decoded, 'columns.phone', ''));
                $legalId = trim((string) Arr::get($decoded, 'columns.legal_id', ''));
                $createdAt = trim((string) Arr::get($decoded, 'columns.created_at', ''));
            } else {
                $global = $rawQ;
            }
        }

        return Person::query()
            ->with([
                'user:id,person_id,name,email,avatar,status',
            ])
            ->when($global !== '', function ($query) use ($global) {
                $query->where(function ($q) use ($global) {
                    $q->where('full_name', 'ilike', "%{$global}%")
                        ->orWhere('email', 'ilike', "%{$global}%")
                        ->orWhere('phone', 'ilike', "%{$global}%")
                        ->orWhere('legal_id', 'ilike', "%{$global}%");
                });
            })
            ->when($fullName !== '', fn ($query) => $query->where('full_name', 'ilike', "%{$fullName}%"))
            ->when($email !== '', fn ($query) => $query->where('email', 'ilike', "%{$email}%"))
            ->when($phone !== '', fn ($query) => $query->where('phone', 'ilike', "%{$phone}%"))
            ->when($legalId !== '', fn ($query) => $query->where('legal_id', 'ilike', "%{$legalId}%"))
            ->when($createdAt !== '', fn ($query) => $query->whereDate('created_at', $createdAt))
            ->orderBy($sort, $dir)
            ->paginate($perPage);
    }

    public function create(array $data, ?UploadedFile $photo = null): Person
    {
        return DB::transaction(function () use ($data, $photo) {
            $payload = $this->extractPersonPayload($data);

            if ($photo) {
                $payload['photo'] = $this->storePhoto($photo, $payload['legal_id']);
            }

            $person = Person::query()->create($payload);

            $this->syncUser($person, $data, true);

            $fresh = $person->refresh()->load([
                'user:id,person_id,name,email,status',
            ]);

            $this->audit()->log(
                actor: auth()->user(),
                event: 'Persona creada',
                subject: $fresh,
                description: __('audit.persons.created'),
                changes: [
                    'old' => null,
                    'new' => Arr::only($fresh->toArray(), [
                        'id',
                        'full_name',
                        'email',
                        'phone',
                        'legal_id',
                        'legal_id_type',
                        'photo',
                    ]),
                ],
                tenantId: $this->resolveCurrentTenantId()
            );

            return $fresh;
        });
    }

    public function update(Person $person, array $data, ?UploadedFile $photo = null): Person
    {
        return DB::transaction(function () use ($person, $data, $photo) {
            $old = Arr::only($person->toArray(), [
                'id',
                'full_name',
                'email',
                'phone',
                'legal_id',
                'legal_id_type',
                'photo',
            ]);

            $payload = $this->extractPersonPayload($data);

            $newLegalId = (string) Arr::get($payload, 'legal_id', $person->legal_id);

            if ($photo) {
                $payload['photo'] = $this->replacePhoto($person->photo, $photo, $newLegalId);
            } elseif ($newLegalId !== $person->legal_id && $person->photo) {
                $payload['photo'] = $this->renamePhoto($person->photo, $newLegalId);
            }

            $person->fill($payload);
            $person->save();

            $this->syncUser($person, $data, false);

            $fresh = $person->refresh()->load([
                'user:id,person_id,name,email,status',
            ]);

            $this->audit()->log(
                actor: auth()->user(),
                event: 'Persona actualizada',
                subject: $fresh,
                description: __('audit.persons.updated'),
                changes: [
                    'old' => $old,
                    'new' => Arr::only($fresh->toArray(), [
                        'id',
                        'full_name',
                        'email',
                        'phone',
                        'legal_id',
                        'legal_id_type',
                        'photo',
                    ]),
                ],
                tenantId: $this->resolveCurrentTenantId()
            );

            return $fresh;
        });
    }

    public function delete(Person $person): void
    {
        DB::transaction(function () use ($person) {
            $snapshot = Arr::only($person->toArray(), [
                'id',
                'full_name',
                'email',
                'phone',
                'legal_id',
                'legal_id_type',
                'photo',
            ]);

            // no eliminare la photo de la persona
           // $this->deletePhoto($person->photo);

            $person->delete();

            $this->audit()->log(
                actor: auth()->user(),
                event: 'Persona eliminada',
                subject: ['type' => Person::class, 'id' => $snapshot['id']],
                description: __('audit.persons.deleted'),
                changes: [
                    'old' => $snapshot,
                    'new' => null,
                ],
                tenantId: $this->resolveCurrentTenantId()
            );
        });
    }

    protected function extractPersonPayload(array $data): array
    {
        return Arr::except($data, [
            'photo',
            'has_user',
            'user_name',
            'user_email',
            'user_password',
            'user_password_confirmation',
            'user_status',
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
