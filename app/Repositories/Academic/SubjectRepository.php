<?php

namespace App\Repositories\Academic;

use App\Models\Academic\Subject;
use App\Models\Administration\Tenant;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class SubjectRepository
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
            abort(400, __('messages.subjects.tenant_not_resolved'));
        }

        $rawQ = trim((string) Arr::get($filters, 'q', ''));
        $sort = Arr::get($filters, 'sort', 'name');
        $dir = strtolower((string) Arr::get($filters, 'dir', 'asc')) === 'desc' ? 'desc' : 'asc';
        $perPage = max(1, min((int) Arr::get($filters, 'per_page', 15), 100));

        if (! in_array($sort, [
            'code',
            'name',
            'is_average',
            'is_behavior',
            'is_active',
            'created_at',
            'updated_at',
        ], true)) {
            $sort = 'name';
        }

        $global = '';
        $code = '';
        $name = '';
        $subjectTypeId = '';
        $evaluationTypeId = '';
        $isAverage = '';
        $isBehavior = '';
        $isActive = '';
        $createdAt = '';

        if ($rawQ !== '') {
            $decoded = json_decode($rawQ, true);

            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $global = trim((string) Arr::get($decoded, 'global', ''));
                $code = trim((string) Arr::get($decoded, 'columns.code', ''));
                $name = trim((string) Arr::get($decoded, 'columns.name', ''));
                $subjectTypeId = trim((string) Arr::get($decoded, 'columns.subject_type_id', ''));
                $evaluationTypeId = trim((string) Arr::get($decoded, 'columns.evaluation_type_id', ''));
                $isAverage = trim((string) Arr::get($decoded, 'columns.is_average', ''));
                $isBehavior = trim((string) Arr::get($decoded, 'columns.is_behavior', ''));
                $isActive = trim((string) Arr::get($decoded, 'columns.is_active', ''));
                $createdAt = trim((string) Arr::get($decoded, 'columns.created_at', ''));
            } else {
                $global = $rawQ;
            }
        }

        return Subject::query()
            ->with([
                'subjectType:id,code,name',
                'evaluationType:id,code,name',
            ])
            ->where('tenant_id', $tenantId)

            ->when($global !== '', function ($query) use ($global) {
                $query->where(function ($q) use ($global) {
                    $q->where('code', 'ilike', "%{$global}%")
                        ->orWhere('name', 'ilike', "%{$global}%")
                        ->orWhere('description', 'ilike', "%{$global}%")
                        ->orWhereHas('subjectType', function ($typeQuery) use ($global) {
                            $typeQuery->where('name', 'ilike', "%{$global}%")
                                ->orWhere('code', 'ilike', "%{$global}%");
                        })
                        ->orWhereHas('evaluationType', function ($typeQuery) use ($global) {
                            $typeQuery->where('name', 'ilike', "%{$global}%")
                                ->orWhere('code', 'ilike', "%{$global}%");
                        });
                });
            })

            ->when($code !== '', fn ($query) => $query->where('code', 'ilike', "%{$code}%"))
            ->when($name !== '', fn ($query) => $query->where('name', 'ilike', "%{$name}%"))
            ->when($subjectTypeId !== '', fn ($query) => $query->where('subject_type_id', $subjectTypeId))
            ->when($evaluationTypeId !== '', fn ($query) => $query->where('evaluation_type_id', $evaluationTypeId))

            ->when($isAverage !== '', fn ($query) => $this->applyBooleanFilter($query, 'is_average', $isAverage))
            ->when($isBehavior !== '', fn ($query) => $this->applyBooleanFilter($query, 'is_behavior', $isBehavior))
            ->when($isActive !== '', fn ($query) => $this->applyBooleanFilter($query, 'is_active', $isActive))

            ->when($createdAt !== '', fn ($query) => $query->whereDate('created_at', $createdAt))

            ->orderBy($sort, $dir)
            ->paginate($perPage);
    }

    public function active(array $filters = []): LengthAwarePaginator
    {
        $tenantId = $this->resolveCurrentTenantId();

        if (! $tenantId) {
            abort(400, __('messages.subjects.tenant_not_resolved'));
        }

        $q = trim((string) Arr::get($filters, 'q', ''));
        $subjectTypeId = trim((string) Arr::get($filters, 'subject_type_id', ''));
        $evaluationTypeId = trim((string) Arr::get($filters, 'evaluation_type_id', ''));
        $perPage = max(1, min((int) Arr::get($filters, 'per_page', 15), 50));

        return Subject::query()
            ->with([
                'subjectType:id,code,name',
                'evaluationType:id,code,name',
            ])
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)

            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($subQuery) use ($q) {
                    $subQuery->where('code', 'ilike', "%{$q}%")
                        ->orWhere('name', 'ilike', "%{$q}%")
                        ->orWhere('description', 'ilike', "%{$q}%");
                });
            })

            ->when($subjectTypeId !== '', fn ($query) => $query->where('subject_type_id', $subjectTypeId))
            ->when($evaluationTypeId !== '', fn ($query) => $query->where('evaluation_type_id', $evaluationTypeId))

            ->orderBy('name')
            ->paginate($perPage);
    }

    public function create(array $data): Subject
    {
        $tenantId = $this->resolveCurrentTenantId();

        if (! $tenantId) {
            abort(400, __('messages.subjects.tenant_not_resolved'));
        }

        $data = $this->normalizeBusinessRules($data);

        $name = (string) Arr::get($data, 'name');

        $code = Arr::get($data, 'code');

        if (! $code) {
            $code = $this->generateCode($tenantId, $name);
        }

        return Subject::query()->create([
            'tenant_id' => $tenantId,
            'subject_type_id' => Arr::get($data, 'subject_type_id'),
            'evaluation_type_id' => Arr::get($data, 'evaluation_type_id'),
            'code' => strtoupper($code),
            'name' => $name,
            'description' => Arr::get($data, 'description'),
            'is_average' => Arr::get($data, 'is_average', true),
            'is_behavior' => Arr::get($data, 'is_behavior', false),
            'is_active' => Arr::get($data, 'is_active', true),
        ])->refresh()->load([
            'subjectType:id,code,name',
            'evaluationType:id,code,name',
        ]);
    }

    public function update(Subject $subject, array $data): Subject
    {
        $tenantId = $this->resolveCurrentTenantId();

        if (! $tenantId) {
            abort(400, __('messages.subjects.tenant_not_resolved'));
        }

        if ((string) $subject->tenant_id !== (string) $tenantId) {
            abort(404);
        }

        $data = $this->normalizeBusinessRules($data);

        $subject->fill([
            'subject_type_id' => Arr::get($data, 'subject_type_id', $subject->subject_type_id),
            'evaluation_type_id' => Arr::get($data, 'evaluation_type_id', $subject->evaluation_type_id),
            'code' => Arr::has($data, 'code')
                ? strtoupper((string) Arr::get($data, 'code'))
                : $subject->code,
            'name' => Arr::get($data, 'name', $subject->name),
            'description' => Arr::get($data, 'description', $subject->description),
            'is_average' => Arr::get($data, 'is_average', $subject->is_average),
            'is_behavior' => Arr::get($data, 'is_behavior', $subject->is_behavior),
            'is_active' => Arr::get($data, 'is_active', $subject->is_active),
        ]);

        $subject->save();

        return $subject->refresh()->load([
            'subjectType:id,code,name',
            'evaluationType:id,code,name',
        ]);
    }

    public function delete(Subject $subject): void
    {
        $tenantId = $this->resolveCurrentTenantId();

        if (! $tenantId) {
            abort(400, __('messages.subjects.tenant_not_resolved'));
        }

        if ((string) $subject->tenant_id !== (string) $tenantId) {
            abort(404);
        }

        $subject->delete();
    }

    protected function applyBooleanFilter($query, string $column, string $value)
    {
        $normalized = strtolower($value);

        if (in_array($normalized, ['1', 'true', 'yes', 'si', 'sí', 'activo', 'active'], true)) {
            return $query->where($column, true);
        }

        if (in_array($normalized, ['0', 'false', 'no', 'inactivo', 'inactive'], true)) {
            return $query->where($column, false);
        }

        return $query;
    }

    protected function generateCode(string $tenantId, string $name): string
    {
        $base = $this->normalizeCodeBase($name);

        if (! $this->codeExists($tenantId, $base)) {
            return $base;
        }

        $prefixTwo = substr($base, 0, 2);

        for ($i = 1; $i <= 9; $i++) {
            $candidate = $prefixTwo.$i;

            if (! $this->codeExists($tenantId, $candidate)) {
                return $candidate;
            }
        }

        $prefixOne = substr($base, 0, 1);

        for ($i = 1; $i <= 99; $i++) {
            $candidate = $prefixOne.str_pad((string) $i, 2, '0', STR_PAD_LEFT);

            if (! $this->codeExists($tenantId, $candidate)) {
                return $candidate;
            }
        }

        abort(422, __('messages.subjects.code_generation_failed'));
    }

    protected function normalizeCodeBase(string $name): string
    {
        $clean = Str::ascii($name);
        $clean = strtoupper(preg_replace('/[^A-Za-z]/', '', $clean));

        if ($clean === '') {
            $clean = 'SUB';
        }

        return str_pad(substr($clean, 0, 3), 3, 'X');
    }

    protected function codeExists(string $tenantId, string $code): bool
    {
        return Subject::query()
            ->where('tenant_id', $tenantId)
            ->where('code', strtoupper($code))
            ->exists();
    }

    protected function normalizeBusinessRules(array $data): array
    {
        if (Arr::get($data, 'is_behavior') === true || Arr::get($data, 'is_behavior') === 'true' || Arr::get($data, 'is_behavior') === 1 || Arr::get($data, 'is_behavior') === '1') {
            $data['is_average'] = false;
        }

        return $data;
    }
}
