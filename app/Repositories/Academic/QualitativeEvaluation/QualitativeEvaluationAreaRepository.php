<?php

namespace App\Repositories\Academic\QualitativeEvaluation;

use App\Models\Academic\QualitativeEvaluationArea;
use App\Models\Administration\Tenant;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

class QualitativeEvaluationAreaRepository
{
    /**
     * @throws ValidationException
     */
    public function list(array $filters = []): LengthAwarePaginator
    {
        $tenantId = $this->resolveCurrentTenantId();

        if (! $tenantId) {
            throw ValidationException::withMessages([
                'tenant' => __('messages.qualitative_evaluation_areas.tenant_not_resolved'),
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

        $code = trim((string) Arr::get($columns, 'code', ''));
        $name = trim((string) Arr::get($columns, 'name', ''));
        $description = trim((string) Arr::get($columns, 'description', ''));
        $isActive = Arr::get($columns, 'is_active', '');

        return QualitativeEvaluationArea::query()
            ->withCount('skills')
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
                'tenant' => __('messages.qualitative_evaluation_areas.tenant_not_resolved'),
            ]);
        }

        $search = trim((string) Arr::get($filters, 'q', ''));
        $perPage = max(1, min((int) Arr::get($filters, 'per_page', 15), 100));

        return QualitativeEvaluationArea::query()
            ->withCount('skills')
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('code', 'ilike', "%{$search}%")
                        ->orWhere('name', 'ilike', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->paginate($perPage);
    }

    public function find(QualitativeEvaluationArea $qualitativeEvaluationArea): QualitativeEvaluationArea
    {
        $tenantId = $this->resolveCurrentTenantId();

        if (! $tenantId || (string) $qualitativeEvaluationArea->tenant_id !== $tenantId) {
            abort(404);
        }

        return $qualitativeEvaluationArea->load($this->relations());
    }

    /**
     * @throws ValidationException
     */
    public function create(array $data): QualitativeEvaluationArea
    {
        $tenantId = $this->resolveCurrentTenantId();

        if (! $tenantId) {
            throw ValidationException::withMessages([
                'tenant' => __('messages.qualitative_evaluation_areas.tenant_not_resolved'),
            ]);
        }

        $area = QualitativeEvaluationArea::query()->create([
            'tenant_id' => $tenantId,
            ...$this->extractAreaPayload($data),
        ]);

        return $this->find($area->refresh());
    }

    public function update(QualitativeEvaluationArea $qualitativeEvaluationArea, array $data): QualitativeEvaluationArea
    {
        $tenantId = $this->resolveCurrentTenantId();

        if (! $tenantId || (string) $qualitativeEvaluationArea->tenant_id !== $tenantId) {
            abort(404);
        }

        $qualitativeEvaluationArea->fill($this->extractAreaPayload($data));
        $qualitativeEvaluationArea->save();

        return $this->find($qualitativeEvaluationArea->refresh());
    }

    public function delete(QualitativeEvaluationArea $qualitativeEvaluationArea): void
    {
        $tenantId = $this->resolveCurrentTenantId();

        if (! $tenantId || (string) $qualitativeEvaluationArea->tenant_id !== $tenantId) {
            abort(404);
        }

        $qualitativeEvaluationArea->delete();
    }

    protected function relations(): array
    {
        return [
            'skills:id,tenant_id,qualitative_evaluation_area_id,code,name,description,is_active',
        ];
    }

    protected function extractAreaPayload(array $data): array
    {
        return Arr::only($data, [
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
