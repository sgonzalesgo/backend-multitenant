<?php

namespace App\Repositories\Administration;

use App\Models\Administration\TenantPosition;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class TenantPositionRepository
{
    public function list(Request $request): LengthAwarePaginator
    {
        $perPage = max(1, min((int) $request->input('per_page', 15), 100));
        $page = max(1, (int) $request->input('page', 1));
        $sort = (string) $request->input('sort', 'tenant');
        $dir = strtolower((string) $request->input('dir', 'asc')) === 'desc' ? 'desc' : 'asc';
        $filters = $this->normalizeFilters($request->input('q'));

        $tenantIdsQuery = TenantPosition::query()
            ->leftJoin('tenants', 'tenant_positions.tenant_id', '=', 'tenants.id')
            ->select('tenant_positions.tenant_id', 'tenants.name as tenant_name')
            ->distinct();

        $this->applyGroupedGlobalFilter($tenantIdsQuery, $filters['global'] ?? '');
        $this->applyGroupedColumnFilters($tenantIdsQuery, $filters['columns'] ?? []);
        $this->applyGroupedSorting($tenantIdsQuery, $sort, $dir);

        $tenantIdsPaginator = $tenantIdsQuery->paginate(
            perPage: $perPage,
            columns: ['tenant_positions.tenant_id', 'tenants.name as tenant_name'],
            pageName: 'page',
            page: $page
        );

        $tenantIds = collect($tenantIdsPaginator->items())
            ->pluck('tenant_id')
            ->values()
            ->all();

        $groups = $this->buildGroupedResults($tenantIds);

        $orderedGroups = collect($tenantIds)
            ->map(fn ($tenantId) => $groups->firstWhere('tenant_id', $tenantId))
            ->filter()
            ->values();

        return new Paginator(
            $orderedGroups,
            $tenantIdsPaginator->total(),
            $tenantIdsPaginator->perPage(),
            $tenantIdsPaginator->currentPage(),
            [
                'path' => request()->url(),
                'query' => request()->query(),
            ]
        );
    }

    public function viewAll(): \Illuminate\Support\Collection
    {
        $tenantIds = TenantPosition::query()
            ->select('tenant_id')
            ->distinct()
            ->orderBy('tenant_id')
            ->pluck('tenant_id')
            ->all();

        return $this->buildGroupedResults($tenantIds);
    }

    public function viewAllByStatus(bool|int $status): \Illuminate\Support\Collection
    {
        $status = (bool) $status;

        $tenantIds = TenantPosition::query()
            ->where('is_active', $status)
            ->select('tenant_id')
            ->distinct()
            ->orderBy('tenant_id')
            ->pluck('tenant_id')
            ->all();

        return $this->buildGroupedResults($tenantIds, function ($query) use ($status) {
            $query->where('is_active', $status);
        });
    }

    public function viewAllByTenant(string $tenantId): ?array
    {
        return $this->buildTenantGroup($tenantId);
    }

    public function syncByTenant(array $data): array
    {
        return DB::transaction(function () use ($data) {
            $tenantId = $data['tenant_id'];
            $items = $data['positions'] ?? [];

            $existingItems = TenantPosition::query()
                ->with(['tenant', 'person', 'position'])
                ->where('tenant_id', $tenantId)
                ->get()
                ->keyBy('id');

            $oldValues = $this->mapPositionsForResponse($existingItems->values());

            $keptIds = [];

            foreach ($items as $item) {
                $tenantPosition = null;

                if (! empty($item['id'])) {
                    $tenantPosition = TenantPosition::query()
                        ->where('tenant_id', $tenantId)
                        ->where('id', $item['id'])
                        ->first();
                }

                if ($tenantPosition) {
                    $payload = $this->dataFormat([
                        ...$item,
                        'tenant_id' => $tenantId,
                    ], $tenantPosition);

                    $tenantPosition->update($payload);
                } else {
                    $payload = $this->dataFormat([
                        ...$item,
                        'tenant_id' => $tenantId,
                    ]);

                    $tenantPosition = TenantPosition::query()->create($payload);
                }

                $keptIds[] = $tenantPosition->id;
            }

            $itemsToDelete = TenantPosition::query()
                ->where('tenant_id', $tenantId)
                ->when(
                    ! empty($keptIds),
                    fn ($query) => $query->whereNotIn('id', $keptIds)
                )
                ->get();

            if (empty($keptIds)) {
                $itemsToDelete = TenantPosition::query()
                    ->where('tenant_id', $tenantId)
                    ->get();
            }

            foreach ($itemsToDelete as $itemToDelete) {
                if ($itemToDelete->signature && Storage::disk('public')->exists($itemToDelete->signature)) {
                    Storage::disk('public')->delete($itemToDelete->signature);
                }

                $itemToDelete->delete();
            }

            $newGroup = $this->buildTenantGroup($tenantId);

            app(AuditLogRepository::class)->log(
                actor: Auth::user(),
                event: 'tenant_position.synced',
                subject: [
                    'type' => TenantPosition::class,
                    'id' => $tenantId,
                ],
                description: __('administration/tenant_position.audit.synced'),
                changes: app(AuditLogRepository::class)->normalizeChangesForTimeline([
                    'old' => $oldValues,
                    'new' => $newGroup['positions'] ?? [],
                ]),
                tenantId: $tenantId
            );

            return $newGroup ?? [
                'tenant_id' => $tenantId,
                'tenant' => null,
                'positions' => [],
            ];
        });
    }

    protected function buildGroupedResults(array $tenantIds, ?callable $positionConstraint = null): \Illuminate\Support\Collection
    {
        if (empty($tenantIds)) {
            return collect();
        }

        $tenantPositions = TenantPosition::query()
            ->with(['tenant', 'person', 'position'])
            ->whereIn('tenant_id', $tenantIds)
            ->when($positionConstraint, fn ($query) => $positionConstraint($query))
            ->orderBy('order_to_sign')
            ->orderBy('created_at')
            ->get()
            ->groupBy('tenant_id');

        return collect($tenantIds)
            ->map(function ($tenantId) use ($tenantPositions) {
                $items = $tenantPositions->get($tenantId, collect());

                if ($items->isEmpty()) {
                    return null;
                }

                $first = $items->first();

                return [
                    'tenant_id' => $tenantId,
                    'tenant' => $first->tenant,
                    'positions' => $this->mapPositionsForResponse($items),
                ];
            })
            ->filter()
            ->values();
    }

    protected function buildTenantGroup(string $tenantId): ?array
    {
        $items = TenantPosition::query()
            ->with(['tenant', 'person', 'position'])
            ->where('tenant_id', $tenantId)
            ->orderBy('order_to_sign')
            ->orderBy('created_at')
            ->get();

        if ($items->isEmpty()) {
            return null;
        }

        $first = $items->first();

        return [
            'tenant_id' => $tenantId,
            'tenant' => $first->tenant,
            'positions' => $this->mapPositionsForResponse($items),
        ];
    }

    protected function mapPositionsForResponse(Collection $items): array
    {
        return $items
            ->map(function (TenantPosition $item) {
                return [
                    'id' => $item->id,
                    'tenant_id' => $item->tenant_id,
                    'person_id' => $item->person_id,
                    'position_id' => $item->position_id,
                    'signature' => $item->signature,
                    'order_to_sign' => $item->order_to_sign,
                    'is_active' => $item->is_active,
                    'start_date' => optional($item->start_date)?->format('Y-m-d'),
                    'end_date' => optional($item->end_date)?->format('Y-m-d'),
                    'created_at' => optional($item->created_at)?->toISOString(),
                    'updated_at' => optional($item->updated_at)?->toISOString(),
                    'person' => $item->person,
                    'position' => $item->position,
                ];
            })
            ->values()
            ->all();
    }

    protected function normalizeFilters(string|array|null $q): array
    {
        if (is_array($q)) {
            return [
                'global' => $q['global'] ?? '',
                'columns' => $q['columns'] ?? [],
            ];
        }

        if (is_string($q) && $q !== '') {
            $decoded = json_decode($q, true);

            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return [
                    'global' => $decoded['global'] ?? '',
                    'columns' => $decoded['columns'] ?? [],
                ];
            }
        }

        return [
            'global' => '',
            'columns' => [],
        ];
    }

    protected function applyGroupedGlobalFilter(Builder $query, string $global): void
    {
        $global = trim($global);

        if ($global === '') {
            return;
        }

        $query->where(function (Builder $subQuery) use ($global) {
            $subQuery
                ->whereHas('tenant', function (Builder $tenantQuery) use ($global) {
                    $tenantQuery
                        ->where('name', 'like', "%{$global}%")
                        ->orWhere('slug', 'like', "%{$global}%")
                        ->orWhere('domain', 'like', "%{$global}%");
                })
                ->orWhereHas('person', function (Builder $personQuery) use ($global) {
                    $personQuery
                        ->where('full_name', 'like', "%{$global}%")
                        ->orWhere('email', 'like', "%{$global}%")
                        ->orWhere('legal_id', 'like', "%{$global}%");
                })
                ->orWhereHas('position', function (Builder $positionQuery) use ($global) {
                    $positionQuery
                        ->where('name', 'like', "%{$global}%")
                        ->orWhere('code', 'like', "%{$global}%");
                });
        });
    }

    protected function applyGroupedColumnFilters(Builder $query, array $columns): void
    {
        $tenant = trim((string) ($columns['tenant'] ?? ''));
        $person = trim((string) ($columns['person'] ?? ''));
        $position = trim((string) ($columns['position'] ?? ''));
        $status = trim((string) ($columns['status'] ?? ''));
        $startDate = trim((string) ($columns['start_date'] ?? ''));
        $endDate = trim((string) ($columns['end_date'] ?? ''));

        if ($tenant !== '') {
            $query->whereHas('tenant', function (Builder $tenantQuery) use ($tenant) {
                $tenantQuery
                    ->where('name', 'like', "%{$tenant}%")
                    ->orWhere('slug', 'like', "%{$tenant}%")
                    ->orWhere('domain', 'like', "%{$tenant}%");
            });
        }

        if ($person !== '') {
            $query->whereHas('person', function (Builder $personQuery) use ($person) {
                $personQuery
                    ->where('full_name', 'like', "%{$person}%")
                    ->orWhere('email', 'like', "%{$person}%")
                    ->orWhere('legal_id', 'like', "%{$person}%");
            });
        }

        if ($position !== '') {
            $query->whereHas('position', function (Builder $positionQuery) use ($position) {
                $positionQuery
                    ->where('name', 'like', "%{$position}%")
                    ->orWhere('code', 'like', "%{$position}%");
            });
        }

        if ($status !== '') {
            $normalizedStatus = match (strtolower($status)) {
                'active', '1', 'true' => true,
                'inactive', '0', 'false' => false,
                default => null,
            };

            if ($normalizedStatus !== null) {
                $query->where('is_active', $normalizedStatus);
            }
        }

        if ($startDate !== '') {
            $query->whereDate('start_date', $startDate);
        }

        if ($endDate !== '') {
            $query->whereDate('end_date', $endDate);
        }
    }

    protected function applyGroupedSorting(Builder $query, string $sort, string $dir): void
    {
        switch ($sort) {
            case 'tenant':
                $query->orderBy('tenant_name', $dir);
                break;

            case 'created_at':
                $query->orderBy('tenant_positions.created_at', $dir);
                break;

            case 'updated_at':
                $query->orderBy('tenant_positions.updated_at', $dir);
                break;

            case 'order_to_sign':
                $query->orderBy('tenant_positions.order_to_sign', $dir);
                break;

            default:
                $query->orderBy('tenant_name', 'asc');
                break;
        }
    }

    protected function dataFormat(array $data, ?TenantPosition $tenantPosition = null): array
    {
        return [
            'tenant_id' => $data['tenant_id'] ?? $tenantPosition?->tenant_id,
            'person_id' => $data['person_id'] ?? $tenantPosition?->person_id,
            'position_id' => $data['position_id'] ?? $tenantPosition?->position_id,
            'signature' => $this->resolveFilePath($data, 'signature', $tenantPosition?->signature),
            'order_to_sign' => $data['order_to_sign'] ?? $tenantPosition?->order_to_sign,
            'is_active' => array_key_exists('is_active', $data)
                ? filter_var($data['is_active'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false
                : ($tenantPosition?->is_active ?? true),
            'start_date' => $data['start_date'] ?? $tenantPosition?->start_date,
            'end_date' => $data['end_date'] ?? $tenantPosition?->end_date,
        ];
    }

    protected function resolveFilePath(array $data, string $field, ?string $currentPath = null): ?string
    {
        if (! isset($data[$field])) {
            return $currentPath;
        }

        if ($data[$field] instanceof UploadedFile) {
            if ($currentPath && Storage::disk('public')->exists($currentPath)) {
                Storage::disk('public')->delete($currentPath);
            }

            return $data[$field]->store('tenant_positions/' . $field, 'public');
        }

        return $currentPath;
    }
}
