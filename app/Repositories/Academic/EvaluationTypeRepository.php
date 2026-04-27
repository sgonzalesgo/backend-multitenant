<?php

namespace App\Repositories\Academic;

use App\Models\Academic\EvaluationType;
use App\Models\Administration\Tenant;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;

class EvaluationTypeRepository
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
            abort(400, __('messages.evaluation_types.tenant_not_resolved'));
        }

        $rawQ = trim((string) Arr::get($filters, 'q', ''));
        $sort = Arr::get($filters, 'sort', 'name');
        $dir = strtolower((string) Arr::get($filters, 'dir', 'asc')) === 'desc' ? 'desc' : 'asc';
        $perPage = max(1, min((int) Arr::get($filters, 'per_page', 15), 100));

        if (! in_array($sort, [
            'code',
            'name',
            'is_active',
            'created_at',
            'updated_at',
        ], true)) {
            $sort = 'name';
        }

        $global = '';
        $code = '';
        $name = '';
        $isActive = '';
        $createdAt = '';

        if ($rawQ !== '') {
            $decoded = json_decode($rawQ, true);

            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $global = trim((string) Arr::get($decoded, 'global', ''));
                $code = trim((string) Arr::get($decoded, 'columns.code', ''));
                $name = trim((string) Arr::get($decoded, 'columns.name', ''));
                $isActive = trim((string) Arr::get($decoded, 'columns.is_active', ''));
                $createdAt = trim((string) Arr::get($decoded, 'columns.created_at', ''));
            } else {
                $global = $rawQ;
            }
        }

        return EvaluationType::query()
            ->where('tenant_id', $tenantId)
            ->when($global !== '', function ($query) use ($global) {
                $query->where(function ($q) use ($global) {
                    $q->where('code', 'ilike', "%{$global}%")
                        ->orWhere('name', 'ilike', "%{$global}%")
                        ->orWhere('description', 'ilike', "%{$global}%");
                });
            })
            ->when($code !== '', fn ($query) => $query->where('code', 'ilike', "%{$code}%"))
            ->when($name !== '', fn ($query) => $query->where('name', 'ilike', "%{$name}%"))
            ->when($isActive !== '', function ($query) use ($isActive) {
                $normalized = strtolower($isActive);

                if (in_array($normalized, ['1', 'true', 'yes', 'si', 'sí', 'activo', 'active'], true)) {
                    $query->where('is_active', true);
                }

                if (in_array($normalized, ['0', 'false', 'no', 'inactivo', 'inactive'], true)) {
                    $query->where('is_active', false);
                }
            })
            ->when($createdAt !== '', fn ($query) => $query->whereDate('created_at', $createdAt))
            ->orderBy($sort, $dir)
            ->paginate($perPage);
    }

    public function active(array $filters = []): LengthAwarePaginator
    {
        $tenantId = $this->resolveCurrentTenantId();

        if (! $tenantId) {
            abort(400, __('messages.evaluation_types.tenant_not_resolved'));
        }

        $q = trim((string) Arr::get($filters, 'q', ''));
        $perPage = max(1, min((int) Arr::get($filters, 'per_page', 100), 100));

        return EvaluationType::query()
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($subQuery) use ($q) {
                    $subQuery->where('code', 'ilike', "%{$q}%")
                        ->orWhere('name', 'ilike', "%{$q}%")
                        ->orWhere('description', 'ilike', "%{$q}%");
                });
            })
            ->orderBy('name')
            ->paginate($perPage);
    }

    public function create(array $data): EvaluationType
    {
        $tenantId = $this->resolveCurrentTenantId();

        if (! $tenantId) {
            abort(400, __('messages.evaluation_types.tenant_not_resolved'));
        }

        return EvaluationType::query()->create([
            'tenant_id' => $tenantId,
            'code' => Arr::get($data, 'code'),
            'name' => Arr::get($data, 'name'),
            'description' => Arr::get($data, 'description'),
            'is_active' => Arr::get($data, 'is_active', true),
        ])->refresh();
    }

    public function update(EvaluationType $evaluationType, array $data): EvaluationType
    {
        $tenantId = $this->resolveCurrentTenantId();

        if (! $tenantId) {
            abort(400, __('messages.evaluation_types.tenant_not_resolved'));
        }

        if ((string) $evaluationType->tenant_id !== (string) $tenantId) {
            abort(404);
        }

        $evaluationType->fill([
            'code' => Arr::get($data, 'code', $evaluationType->code),
            'name' => Arr::get($data, 'name', $evaluationType->name),
            'description' => Arr::get($data, 'description', $evaluationType->description),
            'is_active' => Arr::get($data, 'is_active', $evaluationType->is_active),
        ]);

        $evaluationType->save();

        return $evaluationType->refresh();
    }

    public function delete(EvaluationType $evaluationType): void
    {
        $tenantId = $this->resolveCurrentTenantId();

        if (! $tenantId) {
            abort(400, __('messages.evaluation_types.tenant_not_resolved'));
        }

        if ((string) $evaluationType->tenant_id !== (string) $tenantId) {
            abort(404);
        }

        $evaluationType->delete();
    }
}
