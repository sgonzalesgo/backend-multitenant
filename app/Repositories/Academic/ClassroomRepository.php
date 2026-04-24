<?php

namespace App\Repositories\Academic;

use App\Models\Academic\Classroom;
use App\Models\Administration\Tenant;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;

class ClassroomRepository
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
            abort(400, __('messages.classrooms.tenant_not_resolved'));
        }

        $rawQ = trim((string) Arr::get($filters, 'q', ''));
        $sort = Arr::get($filters, 'sort', 'name');
        $dir = strtolower((string) Arr::get($filters, 'dir', 'asc')) === 'desc' ? 'desc' : 'asc';
        $perPage = max(1, min((int) Arr::get($filters, 'per_page', 15), 100));

        if (! in_array($sort, [
            'code', 'name', 'capacity', 'location',
            'is_active', 'created_at', 'updated_at'
        ], true)) {
            $sort = 'name';
        }

        $global = '';
        $code = '';
        $name = '';
        $capacity = '';
        $location = '';
        $isActive = '';
        $createdAt = '';

        if ($rawQ !== '') {
            $decoded = json_decode($rawQ, true);

            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $global = trim((string) Arr::get($decoded, 'global', ''));
                $code = trim((string) Arr::get($decoded, 'columns.code', ''));
                $name = trim((string) Arr::get($decoded, 'columns.name', ''));
                $capacity = trim((string) Arr::get($decoded, 'columns.capacity', ''));
                $location = trim((string) Arr::get($decoded, 'columns.location', ''));
                $isActive = trim((string) Arr::get($decoded, 'columns.is_active', ''));
                $createdAt = trim((string) Arr::get($decoded, 'columns.created_at', ''));
            } else {
                $global = $rawQ;
            }
        }

        return Classroom::query()
            ->where('tenant_id', $tenantId)

            ->when($global !== '', function ($query) use ($global) {
                $query->where(function ($q) use ($global) {
                    $q->where('code', 'ilike', "%{$global}%")
                        ->orWhere('name', 'ilike', "%{$global}%")
                        ->orWhere('location', 'ilike', "%{$global}%")
                        ->orWhere('description', 'ilike', "%{$global}%");
                });
            })

            ->when($code !== '', fn ($q) => $q->where('code', 'ilike', "%{$code}%"))
            ->when($name !== '', fn ($q) => $q->where('name', 'ilike', "%{$name}%"))
            ->when($location !== '', fn ($q) => $q->where('location', 'ilike', "%{$location}%"))

            ->when($capacity !== '', fn ($q) => $q->where('capacity', $capacity))

            ->when($isActive !== '', function ($q) use ($isActive) {
                $normalized = strtolower($isActive);

                if (in_array($normalized, ['1','true','yes','activo','active'], true)) {
                    $q->where('is_active', true);
                }

                if (in_array($normalized, ['0','false','no','inactivo','inactive'], true)) {
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

        return Classroom::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    public function create(array $data): Classroom
    {
        $tenantId = $this->resolveCurrentTenantId();

        return Classroom::create([
            'tenant_id' => $tenantId,
            ...$data,
        ])->refresh();
    }

    public function update(Classroom $classroom, array $data): Classroom
    {
        $tenantId = $this->resolveCurrentTenantId();

        if ($classroom->tenant_id !== $tenantId) {
            abort(404);
        }

        $classroom->update($data);

        return $classroom->refresh();
    }

    public function delete(Classroom $classroom): void
    {
        $tenantId = $this->resolveCurrentTenantId();

        if ($classroom->tenant_id !== $tenantId) {
            abort(404);
        }

        $classroom->delete();
    }

    public function findOrFail(Classroom $classroom): Classroom
    {
        $tenantId = $this->resolveCurrentTenantId();

        if ($classroom->tenant_id !== $tenantId) {
            abort(404);
        }

        return $classroom;
    }
}
