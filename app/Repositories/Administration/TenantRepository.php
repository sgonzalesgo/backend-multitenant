<?php
//
//namespace App\Repositories\Administration;
//
//use App\Models\Administration\Tenant;
//use Illuminate\Database\Eloquent\Collection;
//use Illuminate\Http\UploadedFile;
//use Illuminate\Support\Facades\Auth;
//use Illuminate\Support\Facades\DB;
//use Illuminate\Support\Facades\Storage;
//
//class TenantRepository
//{
//    public function viewAll(): Collection
//    {
//        return Tenant::query()
//            ->orderBy('name', 'asc')
//            ->get();
//    }
//
//    public function viewAllByStatus(bool|int $status): Collection
//    {
//        return Tenant::query()
//            ->where('is_active', (bool) $status)
//            ->orderBy('name', 'asc')
//            ->get();
//    }
//
//    public function showById(string $id): ?Tenant
//    {
//        return Tenant::query()->find($id);
//    }
//
//    public function create(array $data): Tenant
//    {
//        return DB::transaction(function () use ($data) {
//            $payload = $this->dataFormat($data);
//
//            $tenant = Tenant::create($payload);
//
//            app(AuditLogRepository::class)->log(
//                actor: Auth::user(),
//                event: 'tenant.created',
//                subject: $tenant,
//                description: __('administration/tenant.audit.created'),
//                changes: [
//                    'old' => null,
//                    'new' => $tenant->toArray(),
//                ],
//                tenantId: $tenant->id
//            );
//
//            return $tenant;
//        });
//    }
//
//    public function update(string $id, array $data): ?Tenant
//    {
//        return DB::transaction(function () use ($id, $data) {
//            $tenant = Tenant::query()->find($id);
//
//            if (! $tenant) {
//                return null;
//            }
//
//            $oldValues = $tenant->toArray();
//
//            $payload = $this->dataFormat($data, $tenant);
//
//            $tenant->update($payload);
//
//            app(AuditLogRepository::class)->log(
//                actor: Auth::user(),
//                event: 'tenant.updated',
//                subject: $tenant,
//                description: __('administration/tenant.audit.updated'),
//                changes: [
//                    'old' => $oldValues,
//                    'new' => $tenant->fresh()->toArray(),
//                ],
//                tenantId: $tenant->id
//            );
//
//            return $tenant->fresh();
//        });
//    }
//
//    protected function dataFormat(array $data, ?Tenant $tenant = null): array
//    {
//        return [
//            'name' => $data['name'],
//            'domain' => $data['domain'],
//            'logo' => $this->resolveFilePath($data, 'logo', $tenant?->logo),
//            'address' => $data['address'] ?? null,
//            'phone' => $data['phone'] ?? null,
//            'email' => $data['email'] ?? null,
//            'legal_id' => $data['legal_id'] ?? null,
//            'legal_id_type' => $data['legal_id_type'] ?? null,
//            'is_active' => array_key_exists('is_active', $data)
//                ? filter_var($data['is_active'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false
//                : ($tenant?->is_active ?? true),
//            'business_name' => $data['business_name'] ?? null,
//            'campus_logo' => $this->resolveFilePath($data, 'campus_logo', $tenant?->campus_logo),
//            'campus_type' => $data['campus_type'] ?? null,
//            'slogan' => $data['slogan'] ?? null,
//            'amie_code' => $data['amie_code'] ?? null,
//            'city' => $data['city'] ?? null,
//            'state' => $data['state'] ?? null,
//            'country' => $data['country'] ?? null,
//            'country_logo' => $this->resolveFilePath($data, 'country_logo', $tenant?->country_logo),
//            'country_logo_position_right' => array_key_exists('country_logo_position_right', $data)
//                ? filter_var($data['country_logo_position_right'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false
//                : ($tenant?->country_logo_position_right ?? false),
//            'zip' => $data['zip'] ?? null,
//        ];
//    }
//
//    protected function resolveFilePath(array $data, string $field, ?string $currentPath = null): ?string
//    {
//        if (! isset($data[$field])) {
//            return $currentPath;
//        }
//
//        if ($data[$field] instanceof UploadedFile) {
//            if ($currentPath && Storage::disk('public')->exists($currentPath)) {
//                Storage::disk('public')->delete($currentPath);
//            }
//
//            return $data[$field]->store('tenants/' . $field, 'public');
//        }
//
//        return $currentPath;
//    }
//}

//------------------------------------------ nueva version ----------------


namespace App\Repositories\Administration;

use App\Models\Administration\Tenant;
use App\Models\Administration\TenantPosition;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class TenantRepository
{
    public function viewAll(): Collection
    {
        return Tenant::query()
            ->with(['tenantPositions.person', 'tenantPositions.position'])
            ->orderBy('name', 'asc')
            ->get();
    }

    public function viewAllByStatus(bool|int $status): Collection
    {
        return Tenant::query()
            ->with(['tenantPositions.person', 'tenantPositions.position'])
            ->where('is_active', (bool)$status)
            ->orderBy('name', 'asc')
            ->get();
    }

    public function showById(string $id): ?Tenant
    {
        return Tenant::query()
            ->with(['tenantPositions.person', 'tenantPositions.position'])
            ->find($id);
    }

    public function create(array $data): Tenant
    {
        return DB::transaction(function () use ($data) {
            $authorities = $data['authorities'] ?? [];
            unset($data['authorities']);

            $payload = $this->dataFormat($data);

            $tenant = Tenant::query()->create($payload);

            $this->syncAuthorities($tenant, $authorities);

            app(AuditLogRepository::class)->log(
                actor: Auth::user(),
                event: __('administration/tenant.audit.created'),
                subject: $tenant,
                description: __('administration/tenant.audit.created'),
                changes: [
                    'old' => null,
                    'new' => $tenant->fresh(['tenantPositions.person', 'tenantPositions.position'])->toArray(),
                ],
                tenantId: $tenant->id
            );

            return $tenant->fresh(['tenantPositions.person', 'tenantPositions.position']);
        });
    }

    public function update(string $id, array $data): ?Tenant
    {
        return DB::transaction(function () use ($id, $data) {
            $tenant = Tenant::query()
                ->with('tenantPositions')
                ->find($id);

            if (!$tenant) {
                return null;
            }

            $oldValues = $tenant->fresh(['tenantPositions.person', 'tenantPositions.position'])->toArray();

            $authorities = $data['authorities'] ?? null;
            unset($data['authorities']);

            $payload = $this->dataFormat($data, $tenant);

            $tenant->update($payload);

            if (is_array($authorities)) {
                $this->syncAuthorities($tenant, $authorities, true);
            }

            app(AuditLogRepository::class)->log(
                actor: Auth::user(),
                event: __('administration/tenant.audit.updated'),
                subject: $tenant,
                description: __('administration/tenant.audit.updated'),
                changes: [
                    'old' => $oldValues,
                    'new' => $tenant->fresh(['tenantPositions.person', 'tenantPositions.position'])->toArray(),
                ],
                tenantId: $tenant->id
            );

            return $tenant->fresh(['tenantPositions.person', 'tenantPositions.position']);
        });
    }

    protected function dataFormat(array $data, ?Tenant $tenant = null): array
    {
        return [
            'name' => $data['name'],
            'domain' => $data['domain'],
            'logo' => $this->resolveFilePath($data, 'logo', $tenant?->logo),
            'address' => $data['address'] ?? null,
            'phone' => $data['phone'] ?? null,
            'email' => $data['email'] ?? null,
            'legal_id' => $data['legal_id'] ?? null,
            'legal_id_type' => $data['legal_id_type'] ?? null,
            'is_active' => array_key_exists('is_active', $data)
                ? filter_var($data['is_active'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false
                : ($tenant?->is_active ?? true),
            'business_name' => $data['business_name'] ?? null,
            'campus_logo' => $this->resolveFilePath($data, 'campus_logo', $tenant?->campus_logo),
            'campus_type' => $data['campus_type'] ?? null,
            'slogan' => $data['slogan'] ?? null,
            'amie_code' => $data['amie_code'] ?? null,
            'city' => $data['city'] ?? null,
            'state' => $data['state'] ?? null,
            'country' => $data['country'] ?? null,
            'country_logo' => $this->resolveFilePath($data, 'country_logo', $tenant?->country_logo),
            'country_logo_position_right' => array_key_exists('country_logo_position_right', $data)
                ? filter_var($data['country_logo_position_right'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false
                : ($tenant?->country_logo_position_right ?? false),
            'zip' => $data['zip'] ?? null,
        ];
    }

    protected function syncAuthorities(Tenant $tenant, array $authorities, bool $replaceExisting = false): void
    {
        if ($replaceExisting) {
            $this->deleteExistingAuthoritiesFiles($tenant);
            TenantPosition::query()
                ->where('tenant_id', $tenant->id)
                ->delete();
        }

        foreach ($authorities as $authority) {
            TenantPosition::query()->create([
                'tenant_id' => $tenant->id,
                'person_id' => $authority['person_id'],
                'position_id' => $authority['position_id'],
                'signature' => $this->storeAuthoritySignature($authority['signature'] ?? null),
                'is_active' => array_key_exists('is_active', $authority)
                    ? filter_var($authority['is_active'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false
                    : true,
                'start_date' => $authority['start_date'] ?? null,
                'end_date' => $authority['end_date'] ?? null,
            ]);
        }
    }

    protected function deleteExistingAuthoritiesFiles(Tenant $tenant): void
    {
        foreach ($tenant->tenantPositions as $tenantPosition) {
            if ($tenantPosition->signature && Storage::disk('public')->exists($tenantPosition->signature)) {
                Storage::disk('public')->delete($tenantPosition->signature);
            }
        }
    }

    protected function storeAuthoritySignature(mixed $file): ?string
    {
        if (!$file instanceof UploadedFile) {
            return null;
        }

        return $file->store('tenant_positions/signature', 'public');
    }

    protected function resolveFilePath(array $data, string $field, ?string $currentPath = null): ?string
    {
        if (!isset($data[$field])) {
            return $currentPath;
        }

        if ($data[$field] instanceof UploadedFile) {
            if ($currentPath && Storage::disk('public')->exists($currentPath)) {
                Storage::disk('public')->delete($currentPath);
            }

            return $data[$field]->store('tenants/' . $field, 'public');
        }

        return $currentPath;
    }
}
