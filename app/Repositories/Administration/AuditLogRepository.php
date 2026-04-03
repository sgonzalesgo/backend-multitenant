<?php

namespace App\Repositories\Administration;

use App\Models\Administration\AuditLog;
use App\Models\Administration\Tenant;
use App\Models\Administration\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

class AuditLogRepository
{
    /** Crear un log genérico */
    public function log(?User $actor, string $event, Model|array|null $subject, ?string $description = null, array $changes = [], ?string $tenantId = null, array $meta = []): AuditLog {
        $auditableType = null;
        $auditableId   = null;

        if ($subject instanceof Model) {
            $auditableType = get_class($subject);
            $auditableId   = (string) $subject->getKey();
        } elseif (is_array($subject)) {
            $auditableType = $subject['type'] ?? null;
            $auditableId   = $subject['id']   ?? null;
        }

        $req = request();

        return AuditLog::create([
            'actor_type'     => $actor ? get_class($actor) : null,
            'actor_id'       => $actor?->getKey(),
            'tenant_id'      => $tenantId,
            'event'          => $event,
            'auditable_type' => $auditableType,
            'auditable_id'   => $auditableId,
            'description'    => $description,
            'old_values'     => $changes['old'] ?? null,
            'new_values'     => $changes['new'] ?? null,
            'meta'           => array_merge($meta, [
                'route'   => optional($req)->path(),
                'method'  => optional($req)->method(),
                'query'   => optional($req)->query(),
                'payload' => optional($req)->except(['password', 'password_confirmation']),
            ]),
            'ip_address'     => optional($req)->ip(),
            'user_agent'     => optional($req)->userAgent(),
        ]);
    }

    /** Listado con filtros */
    public function list(array $filters = []): LengthAwarePaginator
    {
        $q        = Arr::get($filters, 'q');
        $event    = Arr::get($filters, 'event');
        $userId   = Arr::get($filters, 'user_id');
        $type     = Arr::get($filters, 'auditable_type');
        $aid      = Arr::get($filters, 'auditable_id');
        $tenantId = Arr::get($filters, 'tenant_id');
        $from     = Arr::get($filters, 'date_from');
        $to       = Arr::get($filters, 'date_to');
        $perPage  = (int) Arr::get($filters, 'per_page', 15);
        $sort     = Arr::get($filters, 'sort', 'created_at');
        $dir      = strtolower((string) Arr::get($filters, 'dir', 'desc')) === 'asc' ? 'asc' : 'desc';

        $query = AuditLog::query();

        if ($q) {
            $query->where(function ($qq) use ($q) {
                $qq->where('description', 'like', "%{$q}%")
                    ->orWhere('event', 'like', "%{$q}%")
                    ->orWhere('auditable_type', 'like', "%{$q}%")
                    ->orWhere('auditable_id', 'like', "%{$q}%");
            });
        }

        if ($event) {
            $query->where('event', $event);
        }

        if ($userId) {
            $query->where('actor_id', $userId);
        }

        if ($type) {
            $query->where('auditable_type', $type);
        }

        if ($aid) {
            $query->where('auditable_id', $aid);
        }

        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }

        if ($from) {
            $query->where('created_at', '>=', $from);
        }

        if ($to) {
            $query->where('created_at', '<=', $to);
        }

        if (! in_array($sort, ['created_at', 'event', 'actor_id', 'tenant_id'], true)) {
            $sort = 'created_at';
        }

        return $query->orderBy($sort, $dir)->paginate($perPage);
    }

    public function historyBySubject(string $auditableType, string $auditableId, array $filters = []): LengthAwarePaginator
    {
        $tenantId = $this->resolveCurrentTenantId();
        $perPage  = max(1, min((int) Arr::get($filters, 'per_page', 15), 100));
        $sort     = Arr::get($filters, 'sort', 'created_at');
        $dir      = strtolower((string) Arr::get($filters, 'dir', 'desc')) === 'asc' ? 'asc' : 'desc';

        if (! in_array($sort, ['created_at', 'event'], true)) {
            $sort = 'created_at';
        }

        return AuditLog::query()
            ->with(['actor', 'auditable'])
            ->where('auditable_type', $auditableType)
            ->where('auditable_id', $auditableId)
            ->orderBy($sort, $dir)
            ->paginate($perPage);
    }

    protected function resolveCurrentTenantId(): ?string
    {
        return Tenant::current()?->id
            ? (string) Tenant::current()?->id
            : null;
    }
}
