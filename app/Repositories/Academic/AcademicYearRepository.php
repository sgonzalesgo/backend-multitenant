<?php

namespace App\Repositories\Academic;

use App\Models\Academic\AcademicYear;
use App\Models\Administration\Tenant;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class AcademicYearRepository
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
            abort(400, __('messages.academic_years.tenant_not_resolved'));
        }

        $rawQ = trim((string) Arr::get($filters, 'q', ''));
        $sort = Arr::get($filters, 'sort', 'start_date');
        $dir = strtolower((string) Arr::get($filters, 'dir', 'desc')) === 'asc' ? 'asc' : 'desc';
        $perPage = max(1, min((int) Arr::get($filters, 'per_page', 15), 100));

        if (! in_array($sort, [
            'code',
            'name',
            'start_date',
            'end_date',
            'is_active',
            'is_current',
            'created_at',
            'updated_at',
        ], true)) {
            $sort = 'start_date';
        }

        $global = '';
        $code = '';
        $name = '';
        $isActive = '';
        $isCurrent = '';
        $startDate = '';
        $endDate = '';
        $createdAt = '';

        if ($rawQ !== '') {
            $decoded = json_decode($rawQ, true);

            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $global = trim((string) Arr::get($decoded, 'global', ''));
                $code = trim((string) Arr::get($decoded, 'columns.code', ''));
                $name = trim((string) Arr::get($decoded, 'columns.name', ''));
                $isActive = trim((string) Arr::get($decoded, 'columns.is_active', ''));
                $isCurrent = trim((string) Arr::get($decoded, 'columns.is_current', ''));
                $startDate = trim((string) Arr::get($decoded, 'columns.start_date', ''));
                $endDate = trim((string) Arr::get($decoded, 'columns.end_date', ''));
                $createdAt = trim((string) Arr::get($decoded, 'columns.created_at', ''));
            } else {
                $global = $rawQ;
            }
        }

        return AcademicYear::query()
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

                if (in_array($normalized, ['1', 'true', 'yes', 'activo', 'active'], true)) {
                    $query->where('is_active', true);
                }

                if (in_array($normalized, ['0', 'false', 'no', 'inactivo', 'inactive'], true)) {
                    $query->where('is_active', false);
                }
            })
            ->when($isCurrent !== '', function ($query) use ($isCurrent) {
                $normalized = strtolower($isCurrent);

                if (in_array($normalized, ['1', 'true', 'yes', 'actual', 'current'], true)) {
                    $query->where('is_current', true);
                }

                if (in_array($normalized, ['0', 'false', 'no', 'no actual', 'not current'], true)) {
                    $query->where('is_current', false);
                }
            })
            ->when($startDate !== '', fn ($query) => $query->whereDate('start_date', $startDate))
            ->when($endDate !== '', fn ($query) => $query->whereDate('end_date', $endDate))
            ->when($createdAt !== '', fn ($query) => $query->whereDate('created_at', $createdAt))
            ->orderBy($sort, $dir)
            ->paginate($perPage);
    }

    public function create(array $data): AcademicYear
    {
        $tenantId = $this->resolveCurrentTenantId();

        if (! $tenantId) {
            abort(400, __('messages.academic_years.tenant_not_resolved'));
        }

        return DB::transaction(function () use ($data, $tenantId) {
            $isCurrent = (bool) Arr::get($data, 'is_current', false);

            if ($isCurrent) {
                AcademicYear::query()
                    ->where('tenant_id', $tenantId)
                    ->where('is_current', true)
                    ->update(['is_current' => false]);
            }

            $academicYear = AcademicYear::query()->create([
                'tenant_id' => $tenantId,
                'code' => Arr::get($data, 'code'),
                'name' => Arr::get($data, 'name'),
                'description' => Arr::get($data, 'description'),
                'start_date' => Arr::get($data, 'start_date'),
                'end_date' => Arr::get($data, 'end_date'),
                'is_active' => Arr::get($data, 'is_active', true),
                'is_current' => $isCurrent,
            ]);

            return $academicYear->refresh();
        });
    }

    public function update(AcademicYear $academicYear, array $data): AcademicYear
    {
        $tenantId = $this->resolveCurrentTenantId();

        if (! $tenantId) {
            abort(400, __('messages.academic_years.tenant_not_resolved'));
        }

        if ((string) $academicYear->tenant_id !== (string) $tenantId) {
            abort(404);
        }

        return DB::transaction(function () use ($academicYear, $data, $tenantId) {
            $isCurrent = Arr::has($data, 'is_current')
                ? (bool) Arr::get($data, 'is_current')
                : $academicYear->is_current;

            if ($isCurrent) {
                AcademicYear::query()
                    ->where('tenant_id', $tenantId)
                    ->where('id', '!=', $academicYear->id)
                    ->where('is_current', true)
                    ->update(['is_current' => false]);
            }

            $academicYear->fill([
                'code' => Arr::get($data, 'code', $academicYear->code),
                'name' => Arr::get($data, 'name', $academicYear->name),
                'description' => Arr::get($data, 'description', $academicYear->description),
                'start_date' => Arr::get($data, 'start_date', $academicYear->start_date),
                'end_date' => Arr::get($data, 'end_date', $academicYear->end_date),
                'is_active' => Arr::get($data, 'is_active', $academicYear->is_active),
                'is_current' => $isCurrent,
            ]);

            $academicYear->save();

            return $academicYear->refresh();
        });
    }

    public function delete(AcademicYear $academicYear): void
    {
        $tenantId = $this->resolveCurrentTenantId();

        if (! $tenantId) {
            abort(400, __('messages.academic_years.tenant_not_resolved'));
        }

        if ((string) $academicYear->tenant_id !== (string) $tenantId) {
            abort(404);
        }

        $academicYear->delete();
    }
}
