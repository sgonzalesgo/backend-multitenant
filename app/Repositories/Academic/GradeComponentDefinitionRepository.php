<?php

namespace App\Repositories\Academic;

use App\Models\Academic\GradeComponentDefinition;
use App\Models\Administration\Tenant;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

class GradeComponentDefinitionRepository
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

        return $user->token()?->tenant_id
            ? (string) $user->token()->tenant_id
            : null;
    }

    /**
     * @throws ValidationException
     */
    protected function requireTenantId(): string
    {
        $tenantId = $this->resolveCurrentTenantId();

        if (! $tenantId) {
            throw ValidationException::withMessages([
                'tenant' => __('messages.grade_component_definition.tenant_not_resolved'),
            ]);
        }

        return $tenantId;
    }

    protected function normalizeQueryState(mixed $q): array
    {
        $default = [
            'global' => '',
            'columns' => [
                'component_key' => '',
                'component_type' => '',
                'code' => '',
                'name' => '',
                'is_active' => '',
                'created_at' => '',
            ],
        ];

        if (! $q) {
            return $default;
        }

        if (is_array($q)) {
            return array_replace_recursive($default, $q);
        }

        if (! is_string($q)) {
            return $default;
        }

        $decoded = json_decode($q, true);

        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return array_replace_recursive($default, $decoded);
        }

        $default['global'] = $q;

        return $default;
    }

    public function index(array $filters = []): LengthAwarePaginator
    {
        $tenantId = $this->requireTenantId();

        $perPage = max(1, min((int) Arr::get($filters, 'per_page', 15), 100));

        $queryState = $this->normalizeQueryState(Arr::get($filters, 'q'));

        $allowedSorts = [
            'component_key',
            'component_type',
            'code',
            'name',
            'is_active',
            'created_at',
        ];

        $sort = Arr::get($filters, 'sort', 'name');
        $sort = in_array($sort, $allowedSorts, true)
            ? $sort
            : 'name';

        $dir = strtolower((string) Arr::get($filters, 'dir', 'asc'));
        $dir = in_array($dir, ['asc', 'desc'], true)
            ? $dir
            : 'asc';

        return GradeComponentDefinition::query()
            ->where('tenant_id', $tenantId)

            ->when(
                Arr::get($queryState, 'global'),
                function ($query, $value) {
                    $query->where(function ($q) use ($value) {
                        $q->where('code', 'ilike', "%{$value}%")
                            ->orWhere('name', 'ilike', "%{$value}%")
                            ->orWhere('component_key', 'ilike', "%{$value}%")
                            ->orWhere('component_type', 'ilike', "%{$value}%");
                    });
                }
            )

            ->when(
                Arr::get($queryState, 'columns.component_key'),
                fn ($query, $value) => $query->where('component_key', 'ilike', "%{$value}%")
            )

            ->when(
                Arr::get($queryState, 'columns.component_type'),
                fn ($query, $value) => $query->where('component_type', $value)
            )

            ->when(
                Arr::get($queryState, 'columns.code'),
                fn ($query, $value) => $query->where('code', 'ilike', "%{$value}%")
            )

            ->when(
                Arr::get($queryState, 'columns.name'),
                fn ($query, $value) => $query->where('name', 'ilike', "%{$value}%")
            )

            ->when(
                Arr::get($queryState, 'columns.is_active') !== null
                && Arr::get($queryState, 'columns.is_active') !== '',
                fn ($query) => $query->where(
                    'is_active',
                    filter_var(
                        Arr::get($queryState, 'columns.is_active'),
                        FILTER_VALIDATE_BOOLEAN
                    )
                )
            )

            ->when(
                Arr::get($queryState, 'columns.created_at'),
                fn ($query, $value) => $query->whereDate('created_at', $value)
            )

            ->orderBy($sort, $dir)
            ->orderBy('id')

            ->paginate($perPage);
    }

    public function store(array $data): GradeComponentDefinition
    {
        $tenantId = $this->requireTenantId();

        return GradeComponentDefinition::query()->create([
            'tenant_id' => $tenantId,
            'component_key' => Arr::get($data, 'component_key'),
            'component_type' => Arr::get($data, 'component_type', 'numeric'),
            'code' => Arr::get($data, 'code'),
            'name' => Arr::get($data, 'name'),
            'description' => Arr::get($data, 'description'),
            'is_active' => (bool) Arr::get($data, 'is_active', true),
        ]);
    }

    public function update(GradeComponentDefinition $definition, array $data): GradeComponentDefinition
    {
        $tenantId = $this->requireTenantId();

        if ((string) $definition->tenant_id !== $tenantId) {
            abort(404);
        }

        $definition->update([
            'component_key' => Arr::get($data, 'component_key', $definition->component_key),
            'component_type' => Arr::get($data, 'component_type', $definition->component_type),
            'code' => Arr::get($data, 'code', $definition->code),
            'name' => Arr::get($data, 'name', $definition->name),
            'description' => Arr::get($data, 'description', $definition->description),
            'is_active' => Arr::has($data, 'is_active')
                ? (bool) Arr::get($data, 'is_active')
                : $definition->is_active,
        ]);

        return $definition->refresh();
    }

    public function delete(GradeComponentDefinition $definition): void
    {
        $tenantId = $this->requireTenantId();

        if ((string) $definition->tenant_id !== $tenantId) {
            abort(404);
        }

        if ($definition->templateItems()->exists()) {
            throw ValidationException::withMessages([
                'grade_component_definition_id' => __('messages.grade_component_definition.in_use'),
            ]);
        }

        $definition->delete();
    }
}
