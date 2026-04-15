<?php

namespace App\Repositories\General;

use App\Http\Requests\General\Department\StoreDepartmentRequest;
use App\Http\Requests\General\Department\UpdateDepartmentRequest;
use App\Models\Administration\Tenant;
use App\Models\General\Department;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DepartmentRepository
{
    protected function resolveCurrentTenantId(): ?string
    {
        if ($current = Tenant::current()) {
            return (string) $current->id;
        }

        $user = auth()->user();

        if (!$user || !method_exists($user, 'token')) {
            return null;
        }

        $token = $user->token();

        if (!$token || empty($token->tenant_id)) {
            return null;
        }

        return (string) $token->tenant_id;
    }

    /**
     * @throws ValidationException
     */
    protected function currentTenantIdOrFail(): string
    {
        $tenantId = $this->resolveCurrentTenantId();

        if (!$tenantId) {
            throw ValidationException::withMessages([
                'tenant' => [__('messages.tenants.tenant_not_found')],
            ]);
        }

        return $tenantId;
    }

    protected function baseQuery(?string $tenantId = null): Builder
    {
        $tenantId ??= $this->currentTenantIdOrFail();

        return Department::query()
            ->with([
                'person:id,full_name,legal_id,legal_id_type,email,phone,photo',
                'tenant:id,name',
            ])
            ->where('tenant_id', $tenantId);
    }

    /**
     * @throws ValidationException
     */
    protected function ensureUniqueCode(string $code, string $tenantId, ?string $ignoreDepartmentId = null): void
    {
        $query = Department::query()
            ->where('tenant_id', $tenantId)
            ->whereRaw('LOWER(code) = ?', [mb_strtolower(trim($code))]);

        if ($ignoreDepartmentId) {
            $query->where('id', '!=', $ignoreDepartmentId);
        }

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'code' => [__('messages.departments.code_taken')],
            ]);
        }
    }

    protected function normalizeStatusFilter(?string $value): string
    {
        $status = mb_strtolower(trim((string) $value));

        return match ($status) {
            'active', 'inactive' => $status,
            default => '',
        };
    }

    public function list(array $filters = []): LengthAwarePaginator
    {
        $tenantId = $this->currentTenantIdOrFail();

        $rawQ = trim((string) Arr::get($filters, 'q', ''));
        $sort = Arr::get($filters, 'sort', 'name');
        $dir = strtolower((string) Arr::get($filters, 'dir', 'asc')) === 'desc' ? 'desc' : 'asc';
        $perPage = max(1, min((int) Arr::get($filters, 'per_page', 15), 100));

        if (!in_array($sort, ['name', 'code', 'status', 'created_at', 'updated_at'], true)) {
            $sort = 'name';
        }

        $global = '';
        $name = '';
        $code = '';
        $status = '';
        $person = '';
        $createdAtInput = '';

        if ($rawQ !== '') {
            $decoded = json_decode($rawQ, true);

            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $global = trim((string) Arr::get($decoded, 'global', ''));
                $name = trim((string) Arr::get($decoded, 'columns.name', ''));
                $code = trim((string) Arr::get($decoded, 'columns.code', ''));
                $status = $this->normalizeStatusFilter(Arr::get($decoded, 'columns.status', ''));
                $person = trim((string) Arr::get($decoded, 'columns.person', ''));
                $createdAtInput = trim((string) Arr::get($decoded, 'columns.created_at', ''));
            } else {
                $global = $rawQ;
            }
        }

        $departmentsTable = (new Department())->getTable();

        $query = $this->baseQuery($tenantId);

        $query
            ->when($global !== '', function ($query) use ($global, $departmentsTable) {
                $query->where(function ($sub) use ($global, $departmentsTable) {
                    $sub->where("{$departmentsTable}.name", 'ilike', "%{$global}%")
                        ->orWhere("{$departmentsTable}.code", 'ilike', "%{$global}%")
                        ->orWhere("{$departmentsTable}.status", 'ilike', "%{$global}%")
                        ->orWhereHas('person', function ($personQuery) use ($global) {
                            $personQuery->where('full_name', 'ilike', "%{$global}%")
                                ->orWhere('legal_id', 'ilike', "%{$global}%")
                                ->orWhere('email', 'ilike', "%{$global}%");
                        });
                });
            })
            ->when($name !== '', function ($query) use ($name, $departmentsTable) {
                $query->where("{$departmentsTable}.name", 'ilike', "%{$name}%");
            })
            ->when($code !== '', function ($query) use ($code, $departmentsTable) {
                $query->where("{$departmentsTable}.code", 'ilike', "%{$code}%");
            })
            ->when($status !== '', function ($query) use ($status, $departmentsTable) {
                $query->whereRaw("LOWER({$departmentsTable}.status) = ?", [$status]);
            })
            ->when($person !== '', function ($query) use ($person) {
                $query->whereHas('person', function ($personQuery) use ($person) {
                    $personQuery->where('full_name', 'ilike', "%{$person}%")
                        ->orWhere('legal_id', 'ilike', "%{$person}%")
                        ->orWhere('email', 'ilike', "%{$person}%");
                });
            })
            ->when($createdAtInput !== '', function ($query) use ($createdAtInput, $departmentsTable) {
                $query->whereDate("{$departmentsTable}.created_at", $createdAtInput);
            });

        return $query
            ->orderBy("{$departmentsTable}.{$sort}", $dir)
            ->paginate($perPage);
    }

    /**
     * @throws ValidationException
     */
    public function all(): Collection
    {
        $tenantId = $this->currentTenantIdOrFail();

        return $this->baseQuery($tenantId)
            ->orderBy('name')
            ->get();
    }

    public function findOrFail(string $id): Department
    {
        $tenantId = $this->currentTenantIdOrFail();

        return $this->baseQuery($tenantId)
            ->whereKey($id)
            ->firstOrFail();
    }

    public function create(StoreDepartmentRequest $req): Department
    {
        return DB::transaction(function () use ($req) {
            $tenantId = $this->currentTenantIdOrFail();
            $data = $req->validated();

            $this->ensureUniqueCode($data['code'], $tenantId);

            $department = new Department();
            $department->name = $data['name'];
            $department->code = $data['code'];
            $department->person_id = $data['person_id'] ?? null;
            $department->tenant_id = $tenantId;
            $department->status = $data['status'] ?? 'active';
            $department->save();

            return $department->refresh()->load(['person', 'tenant']);
        });
    }

    public function update(Department $department, UpdateDepartmentRequest $req): Department
    {
        return DB::transaction(function () use ($department, $req) {
            $tenantId = $this->currentTenantIdOrFail();

            $scopedDepartment = $this->baseQuery($tenantId)
                ->whereKey($department->id)
                ->firstOrFail();

            $data = $req->validated();

            if (array_key_exists('code', $data)) {
                $this->ensureUniqueCode($data['code'], $tenantId, $scopedDepartment->id);
                $scopedDepartment->code = $data['code'];
            }

            if (array_key_exists('name', $data)) {
                $scopedDepartment->name = $data['name'];
            }

            if (array_key_exists('person_id', $data)) {
                $scopedDepartment->person_id = $data['person_id'];
            }

            if (array_key_exists('status', $data)) {
                $scopedDepartment->status = $data['status'];
            }

            $scopedDepartment->save();

            return $scopedDepartment->refresh()->load(['person', 'tenant']);
        });
    }

    public function delete(Department $department): void
    {
        DB::transaction(function () use ($department) {
            $tenantId = $this->currentTenantIdOrFail();

            $scopedDepartment = $this->baseQuery($tenantId)
                ->whereKey($department->id)
                ->firstOrFail();

            $scopedDepartment->delete();
        });
    }
}
