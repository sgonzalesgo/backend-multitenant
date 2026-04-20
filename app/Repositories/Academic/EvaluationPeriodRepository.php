<?php

namespace App\Repositories\Academic;

use App\Models\Academic\EvaluationPeriod;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;

class EvaluationPeriodRepository
{
    public function list(array $filters = []): LengthAwarePaginator
    {
        $rawQ = trim((string) Arr::get($filters, 'q', ''));
        $sort = Arr::get($filters, 'sort', 'default_order');
        $dir = strtolower((string) Arr::get($filters, 'dir', 'asc')) === 'desc' ? 'desc' : 'asc';
        $perPage = max(1, min((int) Arr::get($filters, 'per_page', 15), 100));

        if (! in_array($sort, [
            'code',
            'name',
            'default_order',
            'is_active',
            'created_at',
            'updated_at',
        ], true)) {
            $sort = 'default_order';
        }

        $global = '';
        $code = '';
        $name = '';
        $defaultOrder = '';
        $isActive = '';
        $createdAt = '';

        if ($rawQ !== '') {
            $decoded = json_decode($rawQ, true);

            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $global = trim((string) Arr::get($decoded, 'global', ''));
                $code = trim((string) Arr::get($decoded, 'columns.code', ''));
                $name = trim((string) Arr::get($decoded, 'columns.name', ''));
                $defaultOrder = trim((string) Arr::get($decoded, 'columns.default_order', ''));
                $isActive = trim((string) Arr::get($decoded, 'columns.is_active', ''));
                $createdAt = trim((string) Arr::get($decoded, 'columns.created_at', ''));
            } else {
                $global = $rawQ;
            }
        }

        return EvaluationPeriod::query()
            ->when($global !== '', function ($query) use ($global) {
                $query->where(function ($q) use ($global) {
                    $q->where('code', 'ilike', "%{$global}%")
                        ->orWhere('name', 'ilike', "%{$global}%")
                        ->orWhere('description', 'ilike', "%{$global}%");
                });
            })
            ->when($code !== '', fn ($query) => $query->where('code', 'ilike', "%{$code}%"))
            ->when($name !== '', fn ($query) => $query->where('name', 'ilike', "%{$name}%"))
            ->when($defaultOrder !== '', fn ($query) => $query->where('default_order', (int) $defaultOrder))
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

    public function create(array $data): EvaluationPeriod
    {
        $evaluationPeriod = EvaluationPeriod::query()->create([
            'code' => Arr::get($data, 'code'),
            'name' => Arr::get($data, 'name'),
            'description' => Arr::get($data, 'description'),
            'default_order' => Arr::get($data, 'default_order', 1),
            'is_active' => Arr::get($data, 'is_active', true),
        ]);

        return $evaluationPeriod->refresh();
    }

    public function update(EvaluationPeriod $evaluationPeriod, array $data): EvaluationPeriod
    {
        $evaluationPeriod->fill([
            'code' => Arr::get($data, 'code', $evaluationPeriod->code),
            'name' => Arr::get($data, 'name', $evaluationPeriod->name),
            'description' => Arr::get($data, 'description', $evaluationPeriod->description),
            'default_order' => Arr::get($data, 'default_order', $evaluationPeriod->default_order),
            'is_active' => Arr::get($data, 'is_active', $evaluationPeriod->is_active),
        ]);

        $evaluationPeriod->save();

        return $evaluationPeriod->refresh();
    }

    public function delete(EvaluationPeriod $evaluationPeriod): void
    {
        $evaluationPeriod->delete();
    }
}
