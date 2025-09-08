<?php

namespace App\Repositories\Administration;

use App\Models\Administration\Role;
use App\Models\Administration\User;
use App\Models\Administration\Tenant;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

class RoleRepository
{
    public function __construct(
        protected ?AuditLogRepository $audit = null
    ) {}

    protected function audit(): AuditLogRepository
    {
        return $this->audit ??= app(AuditLogRepository::class);
    }

    // ===== CRUD (roles por tenant) =====

    public function list(array $filters = []): LengthAwarePaginator
    {
        $q        = Arr::get($filters, 'q');
        $sort     = Arr::get($filters, 'sort', 'name');
        $dir      = strtolower((string) Arr::get($filters, 'dir', 'asc')) === 'desc' ? 'desc' : 'asc';
        $perPage  = (int) (Arr::get($filters, 'per_page', 15));
        $tenantId = Arr::get($filters, 'tenant_id'); // opcional: filtrar por tenant

        $query = Role::query();

        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }

        if ($q) {
            $query->where('name', 'like', "%{$q}%");
        }

        if (! in_array($sort, ['name','created_at','updated_at'], true)) {
            $sort = 'name';
        }

        return $query->orderBy($sort, $dir)->paginate($perPage);
    }

    public function all(int|string|null $tenantId = null): Collection
    {
        $q = Role::query();
        if ($tenantId) $q->where('tenant_id', $tenantId);
        return $q->orderBy('name')->get();

    }

    public function findOrFail(int|string $id): Role
    {
        return Role::query()->findOrFail($id);

    }

    public function create(array $data): Role
    {
        $guard = Arr::get($data, 'guard_name', config('auth.defaults.guard', 'api'));

        return DB::transaction(function () use ($data, $guard) {
            $role = Role::create([
                'name'       => $data['name'],
                'guard_name' => $guard,
                'tenant_id'  => $data['tenant_id'],
            ]);

            app(PermissionRegistrar::class)->forgetCachedPermissions();

            // Audit
            $this->audit()->log(
                actor: auth()->user(),
                event: 'Rol creado',
                subject: $role,
                description: __('audit.roles.created'),
                changes: ['old' => null, 'new' => Arr::only($role->toArray(), ['id','name','guard_name','tenant_id'])],
                tenantId: $role->tenant_id
            );

            return $role;
        });
    }

    public function update(Role $role, array $data): Role
    {
        return DB::transaction(function () use ($role, $data) {
            $old = Arr::only($role->getOriginal(), ['name','guard_name','tenant_id']);

            // en general no se cambia tenant_id de un rol existente (pero lo soportamos si viene)
            $role->fill(Arr::only($data, ['name','guard_name']));
            if (array_key_exists('tenant_id', $data)) {
                $role->tenant_id = $data['tenant_id'];
            }
            $role->save();

            app(PermissionRegistrar::class)->forgetCachedPermissions();

            $new = Arr::only($role->fresh()->toArray(), ['name','guard_name','tenant_id']);

            // Audit
            $this->audit()->log(
                actor: auth()->user(),
                event: 'Rol actualizado',
                subject: $role,
                description: __('audit.roles.updated'),
                changes: ['old' => $old, 'new' => $new],
                tenantId: $role->tenant_id
            );

            return $role->refresh();
        });
    }

    public function delete(Role $role): void
    {
        DB::transaction(function () use ($role) {
            $snapshot = Arr::only($role->toArray(), ['id','name','guard_name','tenant_id']);

            $role->delete();
            app(PermissionRegistrar::class)->forgetCachedPermissions();

            // Audit
            $this->audit()->log(
                actor: auth()->user(),
                event: 'Rol eliminado',
                subject: ['type' => Role::class, 'id' => $snapshot['id']],
                description: __('audit.roles.deleted'),
                changes: ['old' => $snapshot, 'new' => null],
                tenantId: $snapshot['tenant_id']
            );
        });
    }

    // ===== Permisos del rol (globales) =====

    public function permissions(Role|int|string $role): Collection
    {
        $role = $role instanceof Role ? $role : Role::query()->findOrFail($role);
        return$role->permissions()->orderBy('name')->get();
    }

    /** Sync total de permisos del rol (IDs). */
    public function syncPermissionsByIds(Role|int|string $role, array $permissionIds): Role
    {
        $role = $role instanceof Role ? $role : Role::query()->findOrFail($role);

        DB::transaction(function () use ($role, $permissionIds) {
            // Snapshot anterior
            $before = $role->permissions()->pluck('id')->values()->all();

            $role->syncPermissions($permissionIds);
            app(PermissionRegistrar::class)->forgetCachedPermissions();

            // Snapshot nuevo
            $after = $role->permissions()->pluck('id')->values()->all();

            // Audit
            $this->audit()->log(
                actor: auth()->user(),
                event: 'Permisos asignados',
                subject: $role,
                description: __('audit.roles.permissions.sync'),
                changes: [
                    'old' => ['permission_ids' => $before],
                    'new' => ['permission_ids' => $after],
                ],
                tenantId: $role->tenant_id,
                meta: ['role_id' => $role->id]
            );
        });

        return $role->load('permissions');
    }

    // ===== Sync de roles del usuario en tenant (IDs) =====

    /**
     * Reemplaza completamente los roles del usuario en $tenantId.
     * Verifica que TODOS los roles enviados pertenezcan a ese tenant.
     */
    public function syncUserRolesInTenant(User|int|string $user, Tenant|int|string $tenant, array $roleIds): array
    {
        $user   = $user   instanceof User   ? $user   : User::query()->findOrFail($user);
        $tenant = $tenant instanceof Tenant ? $tenant : Tenant::query()->findOrFail($tenant);

        // Validar que los roles pertenecen al mismo tenant
        $count = Role::query()->whereIn('id', $roleIds)->where('tenant_id', $tenant->id)->count();
        if ($count !== count($roleIds)) {
            abort(422, __('validation/administration/assign_roles.custom.roles.*.tenant_mismatch')
                ?? 'One or more roles do not belong to the specified tenant.');
        }

        return DB::transaction(function () use ($user, $tenant, $roleIds) {
            $registrar = app(PermissionRegistrar::class);
            $registrar->setPermissionsTeamId($tenant->id);

            // Roles actuales del user en este tenant (antes)
            $teamFk = config('permission.team_foreign_key', 'tenant_id');
            $before = $user->roles()
                ->wherePivot($teamFk, $tenant->id)
                ->pluck('id')->values()->all();

            // Sync
            $user->syncRoles($roleIds);
            $registrar->forgetCachedPermissions();

            // Después
            $after = $user->roles()
                ->wherePivot($teamFk, $tenant->id)
                ->pluck('id')->values()->all();

            // Audit
            $this->audit()->log(
                actor: auth()->user(),
                event: 'Roles asignados',
                subject: $user,
                description: __('audit.users.roles.sync_in_tenant'),
                changes: [
                    'old' => ['role_ids' => $before],
                    'new' => ['role_ids' => $after],
                ],
                tenantId: $tenant->id,
                meta: ['tenant_id' => $tenant->id, 'user_id' => $user->id]
            );

            return $after;
        });
    }
}
