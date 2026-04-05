<?php

namespace App\Repositories\Administration;

use App\Models\Administration\TenantPosition;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class TenantPositionRepository
{
    public function viewAll(): Collection
    {
        return TenantPosition::query()
            ->with(['tenant', 'person', 'position'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function viewAllByStatus(bool|int $status): Collection
    {
        return TenantPosition::query()
            ->with(['tenant', 'person', 'position'])
            ->where('is_active', (bool) $status)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function viewAllByTenant(string $tenantId): Collection
    {
        return TenantPosition::query()
            ->with(['tenant', 'person', 'position'])
            ->where('tenant_id', $tenantId)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function showById(string $id): ?TenantPosition
    {
        return TenantPosition::query()
            ->with(['tenant', 'person', 'position'])
            ->find($id);
    }

    public function create(array $data): TenantPosition
    {
        return DB::transaction(function () use ($data) {
            $payload = $this->dataFormat($data);

            $tenantPosition = TenantPosition::query()->create($payload);

            app(AuditLogRepository::class)->log(
                actor: Auth::user(),
                event: 'tenant_position.created',
                subject: $tenantPosition,
                description: __('administration/tenant_position.audit.created'),
                changes: [
                    'old' => null,
                    'new' => $tenantPosition->fresh(['tenant', 'person', 'position'])->toArray(),
                ],
                tenantId: $tenantPosition->tenant_id
            );

            return $tenantPosition->fresh(['tenant', 'person', 'position']);
        });
    }

    public function update(string $id, array $data): ?TenantPosition
    {
        return DB::transaction(function () use ($id, $data) {
            $tenantPosition = TenantPosition::query()->find($id);

            if (! $tenantPosition) {
                return null;
            }

            $oldValues = $tenantPosition->fresh(['tenant', 'person', 'position'])->toArray();

            $payload = $this->dataFormat($data, $tenantPosition);

            $tenantPosition->update($payload);

            app(AuditLogRepository::class)->log(
                actor: Auth::user(),
                event: 'tenant_position.updated',
                subject: $tenantPosition,
                description: __('administration/tenant_position.audit.updated'),
                changes: [
                    'old' => $oldValues,
                    'new' => $tenantPosition->fresh(['tenant', 'person', 'position'])->toArray(),
                ],
                tenantId: $tenantPosition->tenant_id
            );

            return $tenantPosition->fresh(['tenant', 'person', 'position']);
        });
    }

    protected function dataFormat(array $data, ?TenantPosition $tenantPosition = null): array
    {
        return [
            'tenant_id' => $data['tenant_id'] ?? $tenantPosition?->tenant_id,
            'person_id' => $data['person_id'] ?? $tenantPosition?->person_id,
            'position_id' => $data['position_id'] ?? $tenantPosition?->position_id,
            'signature' => $this->resolveFilePath($data, 'signature', $tenantPosition?->signature),
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
