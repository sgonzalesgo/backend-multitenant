<?php

namespace App\Repositories\Administration;

use App\Models\Administration\Position;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class PositionRepository
{
    public function paginate(array $params = []): LengthAwarePaginator
    {
        return $this->buildListQuery($params)->paginate(
            perPage: (int) ($params['per_page'] ?? 15),
            columns: ['*'],
            pageName: 'page',
            page: (int) ($params['page'] ?? 1)
        );
    }

    public function paginateByStatus(bool|int $status, array $params = []): LengthAwarePaginator
    {
        return $this->buildListQuery($params)
            ->where('is_active', (bool) $status)
            ->paginate(
                perPage: (int) ($params['per_page'] ?? 15),
                columns: ['*'],
                pageName: 'page',
                page: (int) ($params['page'] ?? 1)
            );
    }

    public function showById(string $id): ?Position
    {
        return Position::query()->find($id);
    }

    public function create(array $data): Position
    {
        return DB::transaction(function () use ($data) {
            $payload = $this->dataFormat($data);

            return Position::create($payload);
        });
    }

    public function update(string $id, array $data): ?Position
    {
        return DB::transaction(function () use ($id, $data) {
            $position = Position::query()->find($id);

            if (! $position) {
                return null;
            }

            $payload = $this->dataFormat($data, $position);

            $position->update($payload);

            return $position->fresh();
        });
    }

    protected function buildListQuery(array $params = []): Builder
    {
        $query = Position::query();

        $q = $this->normalizeQueryFilters($params['q'] ?? null);

        $global = trim((string) ($q['global'] ?? ''));
        $columns = $q['columns'] ?? [];

        if ($global !== '') {
            $query->where(function (Builder $subQuery) use ($global) {
                $subQuery
                    ->where('name', 'ilike', "%{$global}%")
                    ->orWhere('code', 'ilike', "%{$global}%")
                    ->orWhere('description', 'ilike', "%{$global}%");
            });
        }

        if (! empty($columns['name'])) {
            $query->where('name', 'ilike', '%' . trim((string) $columns['name']) . '%');
        }

        if (! empty($columns['code'])) {
            $query->where('code', 'ilike', '%' . trim((string) $columns['code']) . '%');
        }

        if (! empty($columns['description'])) {
            $query->where('description', 'ilike', '%' . trim((string) $columns['description']) . '%');
        }

        if (array_key_exists('is_active', $columns) && $columns['is_active'] !== '' && $columns['is_active'] !== null) {
            $value = filter_var($columns['is_active'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

            if ($value !== null) {
                $query->where('is_active', $value);
            }
        }

        if (! empty($columns['created_at'])) {
            $query->whereDate('created_at', $columns['created_at']);
        }

        $allowedSorts = ['name', 'code', 'description', 'is_active', 'created_at', 'updated_at'];
        $sort = in_array(($params['sort'] ?? 'name'), $allowedSorts, true) ? $params['sort'] : 'name';
        $dir = strtolower((string) ($params['dir'] ?? 'asc')) === 'desc' ? 'desc' : 'asc';

        $query->orderBy($sort, $dir);

        return $query;
    }

    protected function normalizeQueryFilters(null|string|array $q): array
    {
        if (is_string($q)) {
            $decoded = json_decode($q, true);

            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $q = $decoded;
            } else {
                $q = [];
            }
        }

        if (! is_array($q)) {
            $q = [];
        }

        return [
            'global' => $q['global'] ?? '',
            'columns' => [
                'name' => $q['columns']['name'] ?? '',
                'code' => $q['columns']['code'] ?? '',
                'description' => $q['columns']['description'] ?? '',
                'is_active' => $q['columns']['is_active'] ?? '',
                'created_at' => $q['columns']['created_at'] ?? '',
            ],
        ];
    }

    protected function dataFormat(array $data, ?Position $position = null): array
    {
        return [
            'name' => $data['name'],
            'code' => $data['code'] ?? null,
            'description' => $data['description'] ?? null,
            'is_active' => array_key_exists('is_active', $data)
                ? filter_var($data['is_active'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false
                : ($position?->is_active ?? true),
        ];
    }
}
