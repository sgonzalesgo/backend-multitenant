<?php

namespace App\Repositories\Academic\QualitativeEvaluation;

use App\Models\Academic\QualitativeSkillDefinition;
use App\Models\Administration\Tenant;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

class QualitativeSkillDefinitionRepository
{
    /**
     * @throws ValidationException
     */
    public function list(array $filters = []): LengthAwarePaginator
    {
        $tenantId = $this->resolveCurrentTenantId();

        if (! $tenantId) {
            throw ValidationException::withMessages([
                'tenant' => __('messages.qualitative_skill_definitions.tenant_not_resolved'),
            ]);
        }

        $rawQ = Arr::get($filters, 'q', '');
        $sort = Arr::get($filters, 'sort', 'created_at');
        $dir = strtolower((string) Arr::get($filters, 'dir', 'desc')) === 'asc' ? 'asc' : 'desc';
        $perPage = max(1, min((int) Arr::get($filters, 'per_page', 15), 100));

        if (! in_array($sort, [
            'code',
            'name',
            'is_active',
            'created_at',
            'updated_at',
        ], true)) {
            $sort = 'created_at';
        }

        $decodedQ = $this->decodeQuery($rawQ);

        $global = trim((string) Arr::get($decodedQ, 'global', ''));
        $columns = Arr::get($decodedQ, 'columns', []);

        $areaId = trim((string) Arr::get($columns, 'qualitative_evaluation_area_id', ''));
        $areaName = trim((string) Arr::get($columns, 'area_name', ''));
        $code = trim((string) Arr::get($columns, 'code', ''));
        $name = trim((string) Arr::get($columns, 'name', ''));
        $description = trim((string) Arr::get($columns, 'description', ''));
        $isActive = Arr::get($columns, 'is_active', '');

        return QualitativeSkillDefinition::query()
            ->with($this->relations())
            ->where('tenant_id', $tenantId)
            ->when($global !== '', function ($query) use ($global) {
                $query->where(function ($q) use ($global) {
                    $q->where('code', 'ilike', "%{$global}%")
                        ->orWhere('name', 'ilike', "%{$global}%")
                        ->orWhere('description', 'ilike', "%{$global}%")
                        ->orWhereHas('area', function ($areaQuery) use ($global) {
                            $areaQuery->where('code', 'ilike', "%{$global}%")
                                ->orWhere('name', 'ilike', "%{$global}%");
                        });
                });
            })
            ->when($areaId !== '', fn ($query) => $query->where('qualitative_evaluation_area_id', $areaId))
            ->when($areaName !== '', function ($query) use ($areaName) {
                $query->whereHas('area', fn ($q) => $q->where('name', 'ilike', "%{$areaName}%"));
            })
            ->when($code !== '', fn ($query) => $query->where('code', 'ilike', "%{$code}%"))
            ->when($name !== '', fn ($query) => $query->where('name', 'ilike', "%{$name}%"))
            ->when($description !== '', fn ($query) => $query->where('description', 'ilike', "%{$description}%"))
            ->when($isActive !== '', function ($query) use ($isActive) {
                $query->where('is_active', filter_var($isActive, FILTER_VALIDATE_BOOLEAN));
            })
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
                'tenant' => __('messages.qualitative_skill_definitions.tenant_not_resolved'),
            ]);
        }

        $search = trim((string) Arr::get($filters, 'q', ''));
        $areaId = trim((string) Arr::get($filters, 'qualitative_evaluation_area_id', ''));
        $perPage = max(1, min((int) Arr::get($filters, 'per_page', 15), 100));

        return QualitativeSkillDefinition::query()
            ->with($this->relations())
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->when($areaId !== '', fn ($query) => $query->where('qualitative_evaluation_area_id', $areaId))
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('code', 'ilike', "%{$search}%")
                        ->orWhere('name', 'ilike', "%{$search}%")
                        ->orWhereHas('area', function ($areaQuery) use ($search) {
                            $areaQuery->where('name', 'ilike', "%{$search}%");
                        });
                });
            })
            ->orderBy('name')
            ->paginate($perPage);
    }

    public function find(QualitativeSkillDefinition $qualitativeSkillDefinition): QualitativeSkillDefinition
    {
        $tenantId = $this->resolveCurrentTenantId();

        if (! $tenantId || (string) $qualitativeSkillDefinition->tenant_id !== $tenantId) {
            abort(404);
        }

        return $qualitativeSkillDefinition->load($this->relations());
    }

    /**
     * @throws ValidationException
     */
    public function create(array $data): QualitativeSkillDefinition
    {
        $tenantId = $this->resolveCurrentTenantId();

        if (! $tenantId) {
            throw ValidationException::withMessages([
                'tenant' => __('messages.qualitative_skill_definitions.tenant_not_resolved'),
            ]);
        }

        $skill = QualitativeSkillDefinition::query()->create([
            'tenant_id' => $tenantId,
            ...$this->extractSkillPayload($data),
        ]);

        return $this->find($skill->refresh());
    }

    public function update(QualitativeSkillDefinition $qualitativeSkillDefinition, array $data): QualitativeSkillDefinition
    {
        $tenantId = $this->resolveCurrentTenantId();

        if (! $tenantId || (string) $qualitativeSkillDefinition->tenant_id !== $tenantId) {
            abort(404);
        }

        $qualitativeSkillDefinition->fill($this->extractSkillPayload($data));
        $qualitativeSkillDefinition->save();

        return $this->find($qualitativeSkillDefinition->refresh());
    }

    public function delete(QualitativeSkillDefinition $qualitativeSkillDefinition): void
    {
        $tenantId = $this->resolveCurrentTenantId();

        if (! $tenantId || (string) $qualitativeSkillDefinition->tenant_id !== $tenantId) {
            abort(404);
        }

        $qualitativeSkillDefinition->delete();
    }

    public function findManyActiveByIds(array $ids): Collection
    {
        $tenantId = $this->resolveCurrentTenantId();

        if (! $tenantId) {
            return collect();
        }

        return QualitativeSkillDefinition::query()
            ->with($this->relations())
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->whereIn('id', $ids)
            ->get();
    }

    protected function relations(): array
    {
        return [
            'area:id,tenant_id,code,name,description,is_active',
        ];
    }

    protected function extractSkillPayload(array $data): array
    {
        return Arr::only($data, [
            'qualitative_evaluation_area_id',
            'code',
            'name',
            'description',
            'is_active',
        ]);
    }

    protected function decodeQuery(mixed $rawQ): array
    {
        if (is_string($rawQ) && trim($rawQ) !== '') {
            $decoded = json_decode($rawQ, true);

            return is_array($decoded) ? $decoded : [];
        }

        return is_array($rawQ) ? $rawQ : [];
    }

    protected function resolveCurrentTenantId(): ?string
    {
        $currentTenant = Tenant::current();

        return $currentTenant ? (string) $currentTenant->id : null;
    }
}
