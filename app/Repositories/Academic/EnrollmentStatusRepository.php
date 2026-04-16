<?php

namespace App\Repositories\Academic;

use App\Models\Academic\EnrollmentStatus;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;

class EnrollmentStatusRepository
{
    public function list(array $filters = []): LengthAwarePaginator
    {
        $rawQ = trim((string) Arr::get($filters, 'q', ''));
        $sort = Arr::get($filters, 'sort', 'sort_order');
        $dir = strtolower((string) Arr::get($filters, 'dir', 'asc')) === 'desc' ? 'desc' : 'asc';
        $perPage = max(1, min((int) Arr::get($filters, 'per_page', 15), 100));

        if (! in_array($sort, [
            'code',
            'name',
            'is_active',
            'sort_order',
            'created_at',
            'updated_at',
        ], true)) {
            $sort = 'sort_order';
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

        return EnrollmentStatus::query()
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
            ->when($createdAt !== '', fn ($query) => $query->whereDate('created_at', $createdAt))
            ->orderBy($sort, $dir)
            ->paginate($perPage);
    }

    public function create(array $data): EnrollmentStatus
    {
        $enrollmentStatus = EnrollmentStatus::query()->create([
            'code' => Arr::get($data, 'code'),
            'name' => Arr::get($data, 'name'),
            'description' => Arr::get($data, 'description'),
            'is_active' => Arr::get($data, 'is_active', true),
            'sort_order' => Arr::get($data, 'sort_order', 0),
        ]);

        return $enrollmentStatus->refresh();
    }

    public function update(EnrollmentStatus $enrollmentStatus, array $data): EnrollmentStatus
    {
        $enrollmentStatus->fill([
            'code' => Arr::get($data, 'code', $enrollmentStatus->code),
            'name' => Arr::get($data, 'name', $enrollmentStatus->name),
            'description' => Arr::get($data, 'description', $enrollmentStatus->description),
            'is_active' => Arr::get($data, 'is_active', $enrollmentStatus->is_active),
            'sort_order' => Arr::get($data, 'sort_order', $enrollmentStatus->sort_order),
        ]);

        $enrollmentStatus->save();

        return $enrollmentStatus->refresh();
    }

    public function delete(EnrollmentStatus $enrollmentStatus): void
    {
        $enrollmentStatus->delete();
    }
}
