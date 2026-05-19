<?php

namespace App\Repositories\General;

use App\Models\General\AcademicNonWorkingDay;
use App\Models\Administration\Tenant;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

class AcademicNonWorkingDayRepository
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

        return $token?->tenant_id ? (string) $token->tenant_id : null;
    }

    public function list(array $filters = []): LengthAwarePaginator
    {
        $tenantId = $this->requireTenantId();

        $rawQ = Arr::get($filters, 'q', '');
        $sort = Arr::get($filters, 'sort', 'date');
        $dir = strtolower((string) Arr::get($filters, 'dir', 'asc')) === 'desc' ? 'desc' : 'asc';
        $perPage = max(1, min((int) Arr::get($filters, 'per_page', 15), 100));

        if (! in_array($sort, [
            'date',
            'name',
            'type',
            'affects_attendance',
            'affects_calendar',
            'is_active',
            'created_at',
            'updated_at',
        ], true)) {
            $sort = 'date';
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

        return AcademicNonWorkingDay::query()
            ->with($this->relations())
            ->where('tenant_id', $tenantId)

            ->when($global !== '', function ($query) use ($global) {
                $query->where(function ($q) use ($global) {
                    $q->where('name', 'ilike', "%{$global}%")
                        ->orWhere('type', 'ilike', "%{$global}%")
                        ->orWhere('observation', 'ilike', "%{$global}%")
                        ->orWhereHas('academicYear', function ($yearQuery) use ($global) {
                            $yearQuery->where('name', 'ilike', "%{$global}%")
                                ->orWhere('code', 'ilike', "%{$global}%");
                        });
                });
            })

            ->when(Arr::get($columns, 'academic_year_id'), fn ($q, $v) => $q->where('academic_year_id', $v))
            ->when(Arr::get($columns, 'date'), fn ($q, $v) => $q->whereDate('date', $v))
            ->when(Arr::get($columns, 'from_date'), fn ($q, $v) => $q->whereDate('date', '>=', $v))
            ->when(Arr::get($columns, 'to_date'), fn ($q, $v) => $q->whereDate('date', '<=', $v))
            ->when(Arr::get($columns, 'type'), fn ($q, $v) => $q->where('type', $v))

            ->when(Arr::has($columns, 'affects_attendance'), function ($q) use ($columns) {
                $this->applyBooleanFilter($q, 'affects_attendance', Arr::get($columns, 'affects_attendance'));
            })

            ->when(Arr::has($columns, 'affects_calendar'), function ($q) use ($columns) {
                $this->applyBooleanFilter($q, 'affects_calendar', Arr::get($columns, 'affects_calendar'));
            })

            ->when(Arr::has($columns, 'is_active'), function ($q) use ($columns) {
                $this->applyBooleanFilter($q, 'is_active', Arr::get($columns, 'is_active'));
            })

            ->orderBy($sort, $dir)
            ->paginate($perPage);
    }

    public function active(array $filters = [])
    {
        $tenantId = $this->requireTenantId();

        return AcademicNonWorkingDay::query()
            ->with($this->relations())
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->when(Arr::get($filters, 'academic_year_id'), function ($query, $value) {
                $query->where(function ($q) use ($value) {
                    $q->where('academic_year_id', $value)
                        ->orWhereNull('academic_year_id');
                });
            })
            ->when(Arr::get($filters, 'affects_attendance'), function ($query, $value) {
                $this->applyBooleanFilter($query, 'affects_attendance', $value);
            })
            ->when(Arr::get($filters, 'affects_calendar'), function ($query, $value) {
                $this->applyBooleanFilter($query, 'affects_calendar', $value);
            })
            ->orderBy('date')
            ->get();
    }

    public function find(AcademicNonWorkingDay $academicNonWorkingDay): AcademicNonWorkingDay
    {
        $tenantId = $this->requireTenantId();

        if ((string) $academicNonWorkingDay->tenant_id !== $tenantId) {
            abort(404);
        }

        return $academicNonWorkingDay->load($this->relations());
    }

    public function create(array $data): AcademicNonWorkingDay
    {
        $tenantId = $this->requireTenantId();

        $this->ensureDateDoesNotExist($data, $tenantId);

        return AcademicNonWorkingDay::query()
            ->create([
                'tenant_id' => $tenantId,
                ...$this->extractPayload($data),
            ])
            ->load($this->relations());
    }

    public function update(
        AcademicNonWorkingDay $academicNonWorkingDay,
        array $data
    ): AcademicNonWorkingDay {
        $tenantId = $this->requireTenantId();

        if ((string) $academicNonWorkingDay->tenant_id !== $tenantId) {
            abort(404);
        }

        $payloadForValidation = [
            ...$academicNonWorkingDay->only([
                'academic_year_id',
                'date',
            ]),
            ...Arr::only($data, [
                'academic_year_id',
                'date',
            ]),
        ];

        $this->ensureDateDoesNotExistForUpdate(
            $academicNonWorkingDay,
            $payloadForValidation,
            $tenantId
        );

        $academicNonWorkingDay->fill($this->extractPayload($data));
        $academicNonWorkingDay->save();

        return $academicNonWorkingDay->refresh()->load($this->relations());
    }

    public function delete(AcademicNonWorkingDay $academicNonWorkingDay): void
    {
        $tenantId = $this->requireTenantId();

        if ((string) $academicNonWorkingDay->tenant_id !== $tenantId) {
            abort(404);
        }

        $academicNonWorkingDay->delete();
    }

    protected function ensureDateDoesNotExist(array $data, string $tenantId): void
    {
        $exists = AcademicNonWorkingDay::query()
            ->where('tenant_id', $tenantId)
            ->whereDate('date', Arr::get($data, 'date'))
            ->where(function ($query) use ($data) {
                $academicYearId = Arr::get($data, 'academic_year_id');

                if ($academicYearId) {
                    $query->where('academic_year_id', $academicYearId);
                } else {
                    $query->whereNull('academic_year_id');
                }
            })
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'date' => __('messages.academic_non_working_days.already_exists'),
            ]);
        }
    }

    protected function ensureDateDoesNotExistForUpdate(
        AcademicNonWorkingDay $academicNonWorkingDay,
        array $data,
        string $tenantId
    ): void {
        $exists = AcademicNonWorkingDay::query()
            ->where('tenant_id', $tenantId)
            ->where('id', '!=', $academicNonWorkingDay->id)
            ->whereDate('date', Arr::get($data, 'date'))
            ->where(function ($query) use ($data) {
                $academicYearId = Arr::get($data, 'academic_year_id');

                if ($academicYearId) {
                    $query->where('academic_year_id', $academicYearId);
                } else {
                    $query->whereNull('academic_year_id');
                }
            })
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'date' => __('messages.academic_non_working_days.already_exists'),
            ]);
        }
    }

    protected function extractPayload(array $data): array
    {
        return Arr::only($data, [
            'academic_year_id',
            'date',
            'name',
            'type',
            'affects_attendance',
            'affects_calendar',
            'is_active',
            'observation',
        ]);
    }

    protected function relations(): array
    {
        return [
            'academicYear:id,code,name,start_date,end_date,is_active,is_current',
        ];
    }

    protected function applyBooleanFilter($query, string $field, mixed $value): void
    {
        if ($value === null || $value === '') {
            return;
        }

        if (is_bool($value)) {
            $query->where($field, $value);
            return;
        }

        $normalized = strtolower((string) $value);

        if (in_array($normalized, ['1', 'true', 'yes', 'sí', 'si', 'active', 'activo'], true)) {
            $query->where($field, true);
            return;
        }

        if (in_array($normalized, ['0', 'false', 'no', 'inactive', 'inactivo'], true)) {
            $query->where($field, false);
        }
    }

    protected function requireTenantId(): string
    {
        $tenantId = $this->resolveCurrentTenantId();

        if (! $tenantId) {
            throw ValidationException::withMessages([
                'tenant' => __('messages.academic_non_working_days.tenant_not_resolved'),
            ]);
        }

        return $tenantId;
    }
}
