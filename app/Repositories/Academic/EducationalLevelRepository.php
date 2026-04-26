<?php

namespace App\Repositories\Academic;

use App\Models\Academic\EducationalLevel;
use App\Models\Administration\Tenant;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;

class EducationalLevelRepository
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
            abort(400, __('messages.educational_levels.tenant_not_resolved'));
        }

        $rawQ = trim((string) Arr::get($filters, 'q', ''));
        $sort = Arr::get($filters, 'sort', 'sort_order');
        $dir = strtolower((string) Arr::get($filters, 'dir', 'asc')) === 'desc' ? 'desc' : 'asc';
        $perPage = max(1, min((int) Arr::get($filters, 'per_page', 15), 100));

        if (! in_array($sort, [
            'code',
            'name',
            'start_number',
            'end_number',
            'sort_order',
            'has_specialty',
            'is_active',
            'created_at',
            'updated_at',
        ], true)) {
            $sort = 'sort_order';
        }

        $global = '';
        $code = '';
        $name = '';
        $startNumber = '';
        $endNumber = '';
        $sortOrder = '';
        $hasSpecialty = '';
        $isActive = '';
        $createdAt = '';

        if ($rawQ !== '') {
            $decoded = json_decode($rawQ, true);

            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $global = trim((string) Arr::get($decoded, 'global', ''));
                $code = trim((string) Arr::get($decoded, 'columns.code', ''));
                $name = trim((string) Arr::get($decoded, 'columns.name', ''));
                $startNumber = trim((string) Arr::get($decoded, 'columns.start_number', ''));
                $endNumber = trim((string) Arr::get($decoded, 'columns.end_number', ''));
                $sortOrder = trim((string) Arr::get($decoded, 'columns.sort_order', ''));
                $hasSpecialty = trim((string) Arr::get($decoded, 'columns.has_specialty', ''));
                $isActive = trim((string) Arr::get($decoded, 'columns.is_active', ''));
                $createdAt = trim((string) Arr::get($decoded, 'columns.created_at', ''));
            } else {
                $global = $rawQ;
            }
        }

        return EducationalLevel::query()
            ->with('nextEducationalLevel')
            ->where('tenant_id', $tenantId)

            ->when($global !== '', function ($query) use ($global) {
                $query->where(function ($q) use ($global) {
                    $q->where('code', 'ilike', "%{$global}%")
                        ->orWhere('name', 'ilike', "%{$global}%")
                        ->orWhere('description', 'ilike', "%{$global}%");
                });
            })

            ->when($code !== '', fn ($q) => $q->where('code', 'ilike', "%{$code}%"))
            ->when($name !== '', fn ($q) => $q->where('name', 'ilike', "%{$name}%"))

            ->when($startNumber !== '', fn ($q) => $q->where('start_number', $startNumber))
            ->when($endNumber !== '', fn ($q) => $q->where('end_number', $endNumber))
            ->when($sortOrder !== '', fn ($q) => $q->where('sort_order', $sortOrder))

            ->when($hasSpecialty !== '', function ($q) use ($hasSpecialty) {
                $normalized = strtolower($hasSpecialty);

                if (in_array($normalized, ['1', 'true', 'yes', 'sí', 'si', 'active'], true)) {
                    $q->where('has_specialty', true);
                }

                if (in_array($normalized, ['0', 'false', 'no', 'inactive'], true)) {
                    $q->where('has_specialty', false);
                }
            })

            ->when($isActive !== '', function ($q) use ($isActive) {
                $normalized = strtolower($isActive);

                if (in_array($normalized, ['1', 'true', 'yes', 'sí', 'si', 'activo', 'active'], true)) {
                    $q->where('is_active', true);
                }

                if (in_array($normalized, ['0', 'false', 'no', 'inactivo', 'inactive'], true)) {
                    $q->where('is_active', false);
                }
            })

            ->when($createdAt !== '', fn ($q) => $q->whereDate('created_at', $createdAt))

            ->orderBy($sort, $dir)
            ->paginate($perPage);
    }

    public function active(): Collection
    {
        $tenantId = $this->resolveCurrentTenantId();

        if (! $tenantId) {
            abort(400, __('messages.educational_levels.tenant_not_resolved'));
        }

        return EducationalLevel::query()
            ->with('nextEducationalLevel')
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();
    }

    public function create(array $data): EducationalLevel
    {
        $tenantId = $this->resolveCurrentTenantId();

        if (! $tenantId) {
            abort(400, __('messages.educational_levels.tenant_not_resolved'));
        }

        return EducationalLevel::create([
            'tenant_id' => $tenantId,
            ...$data,
        ])->refresh();
    }

    public function update(EducationalLevel $educationalLevel, array $data): EducationalLevel
    {
        $tenantId = $this->resolveCurrentTenantId();

        if ($educationalLevel->tenant_id !== $tenantId) {
            abort(404);
        }

        $educationalLevel->update($data);

        return $educationalLevel->refresh();
    }

    public function delete(EducationalLevel $educationalLevel): void
    {
        $tenantId = $this->resolveCurrentTenantId();

        if ($educationalLevel->tenant_id !== $tenantId) {
            abort(404);
        }

        $educationalLevel->delete();
    }

    public function findOrFail(EducationalLevel $educationalLevel): EducationalLevel
    {
        $tenantId = $this->resolveCurrentTenantId();

        if ($educationalLevel->tenant_id !== $tenantId) {
            abort(404);
        }

        return $educationalLevel->load('nextEducationalLevel');
    }
}
