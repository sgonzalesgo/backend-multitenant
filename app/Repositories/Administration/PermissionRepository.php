<?php

namespace App\Repositories\Administration;

use App\Models\Administration\Permission;
use App\Models\Administration\Role;
use App\Models\Administration\Tenant;
use App\Models\Administration\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use App\Repositories\Administration\AuditLogRepository;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

class PermissionRepository
{
    public function __construct(
        protected ?AuditLogRepository $audit = null
    ) {}

    protected function audit(): AuditLogRepository
    {
        return $this->audit ??= app(AuditLogRepository::class);
    }

    /**
     * Pagina permisos (globales).
     */
    public function list(array $filters = []): LengthAwarePaginator
    {
        $q       = Arr::get($filters, 'q');
        $sort    = Arr::get($filters, 'sort', 'name');
        $dir     = strtolower((string) Arr::get($filters, 'dir', 'asc')) === 'desc' ? 'desc' : 'asc';
        $perPage = (int) (Arr::get($filters, 'per_page', 15));

        $query = Permission::query();

        if ($q) {
            $query->where('name', 'like', "%{$q}%");
        }

        if (! in_array($sort, ['name','created_at','updated_at'], true)) {
            $sort = 'name';
        }

        return$query->orderBy($sort, $dir)->paginate($perPage);
    }

    /** Todos los permisos (ordenados). */
    public function all(): Collection
    {
        return Permission::query()->orderBy('name')->get();

    }

    /** Buscar por ID (404 si no existe). */
    public function findOrFail(int|string $id): Permission
    {
        return Permission::query()->findOrFail($id);
    }

    /** Crear permiso global. */
    public function create(array $data): Permission
    {
        $guard = Arr::get($data, 'guard_name', config('auth.defaults.guard', 'api'));

        return DB::transaction(function () use ($data, $guard) {
            $perm = Permission::create([
                'name'       => $data['name'],
                'guard_name' => $guard,
            ]);

            app(PermissionRegistrar::class)->forgetCachedPermissions();

            // Audit
            $this->audit()->log(
                actor: auth()->user(),
                event: 'Permiso creado',
                subject: $perm,
                description: __('audit.permissions.created'),
                changes: ['old' => null, 'new' => Arr::only($perm->toArray(), ['id','name','guard_name'])],
                tenantId: Tenant::current()?->id
            );

            return $perm;
        });
    }

    /** Actualizar permiso global. */
    public function update(Permission $permission, array $data): Permission
    {
        return DB::transaction(function () use ($permission, $data) {
            $old = Arr::only($permission->getOriginal(), ['name','guard_name']);

            $permission->fill(Arr::only($data, ['name','guard_name']));
            $permission->save();

            app(PermissionRegistrar::class)->forgetCachedPermissions();

            $new = Arr::only($permission->fresh()->toArray(), ['name','guard_name']);

            // Audit
            $this->audit()->log(
                actor: auth()->user(),
                event: 'Permiso actualizado',
                subject: $permission,
                description: __('audit.permissions.updated'),
                changes: ['old' => $old, 'new' => $new],
                tenantId: Tenant::current()?->id
            );

            return $permission->refresh();
        });
    }

    /** Eliminar permiso global. */
    public function delete(Permission $permission): void
    {
        DB::transaction(function () use ($permission) {
            $snapshot = Arr::only($permission->toArray(), ['id','name','guard_name']);

            $permission->delete();
            app(PermissionRegistrar::class)->forgetCachedPermissions();

            // Audit
            $this->audit()->log(
                actor: auth()->user(),
                event: 'Permiso eliminado',
                subject: ['type' => Permission::class, 'id' => $snapshot['id']],
                description: __('audit.permissions.deleted'),
                changes: ['old' => $snapshot, 'new' => null],
                tenantId: Tenant::current()?->id
            );
        });
    }

    // -------------------------------------------------
    // Rol <-> Permisos (por IDs)
    // -------------------------------------------------

    /** Permisos actuales de un rol. */
    public function permissionsForRole(Role|int|string $role): Collection
    {
        $role = $role instanceof Role ? $role : Role::query()->findOrFail($role);
        $list = $role->permissions()->orderBy('name')->get();

        return $list;
    }

    /**
     * Adjunta permisos (por IDs) a un rol, sin quitar los existentes.
     */
    public function attachPermissionsToRoleByIds(Role|int|string $role, array $permissionIds): Role
    {
        $role = $role instanceof Role ? $role : Role::query()->findOrFail($role);

        DB::transaction(function () use ($role, $permissionIds) {
            $role->givePermissionTo($permissionIds);
            app(PermissionRegistrar::class)->forgetCachedPermissions();

            // Audit
            $this->audit()->log(
                actor: auth()->user(),
                event: 'Permisos asignados',
                subject: $role,
                description: __('audit.roles_permissions.attach'),
                changes: ['old' => null, 'new' => ['permission_ids' => array_values($permissionIds)]],
                tenantId: Tenant::current()?->id,
                meta: ['role_id' => $role->id, 'attached_count' => count($permissionIds)]
            );
        });

        return $role->load('permissions');
    }

    /**
     * Revoca permisos (por IDs) de un rol.
     */
    public function revokePermissionsFromRoleByIds(Role|int|string $role, array $permissionIds): Role
    {
        $role = $role instanceof Role ? $role : Role::query()->findOrFail($role);

        DB::transaction(function () use ($role, $permissionIds) {
            foreach ($permissionIds as $pid) {
                $role->revokePermissionTo($pid);
            }
            app(PermissionRegistrar::class)->forgetCachedPermissions();

            // Audit
            $this->audit()->log(
                actor: auth()->user(),
                event: 'Permisos revocados',
                subject: $role,
                description: __('audit.roles_permissions.revoke'),
                changes: ['old' => ['permission_ids' => array_values($permissionIds)], 'new' => null],
                tenantId: Tenant::current()?->id,
                meta: ['role_id' => $role->id, 'revoked_count' => count($permissionIds)]
            );
        });

        return $role->load('permissions');
    }

    /**
     * Sincroniza permisos de un rol (por IDs).
     * $detachMissing: true => reemplaza completamente el set; false => agrega sin quitar.
     */
    public function syncRolePermissionsByIds(Role|int|string $role, array $permissionIds): Role
    {
        $role = $role instanceof Role ? $role : Role::query()->findOrFail($role);

        DB::transaction(function () use ($role, $permissionIds) {
            // Snapshot anterior (ids)
            $before = $role->permissions()->pluck('id')->values()->all();

            $role->syncPermissions($permissionIds);
            app(PermissionRegistrar::class)->forgetCachedPermissions();

            // Snapshot nuevo (ids)
            $after = $role->permissions()->pluck('id')->values()->all();

            // Audit
            $this->audit()->log(
                actor: auth()->user(),
                event: 'Permisos asignados',
                subject: $role,
                description: __('audit.roles_permissions.sync'),
                changes: ['old' => ['permission_ids' => $before], 'new' => ['permission_ids' => $after]],
                tenantId: Tenant::current()?->id,
                meta: ['role_id' => $role->id]
            );
        });

        return $role->load('permissions');
    }

    // -------------------------------------------------
    // Helpers tenant-aware para UI/chequeos
    // -------------------------------------------------

    /**
     * Permisos efectivos de un usuario dentro de un tenant (por sus roles en ese tenant).
     * NOTA: los permisos son globales; el tenant define qué ROLES tiene el usuario.
     */
    public function userPermissionsInTenant(User $user, Tenant|int|string $tenant): array
    {
        $tenant = $tenant instanceof Tenant ? $tenant : Tenant::query()->findOrFail($tenant);

        app(PermissionRegistrar::class)->setPermissionsTeamId($tenant->id);
        return $user->getAllPermissions()->pluck('name')->unique()->values()->all();
    }

    /** Roles del usuario en un tenant (útil para UI). */
    public function userRolesInTenant(User $user, Tenant|int|string $tenant): array
    {
        $tenant = $tenant instanceof Tenant ? $tenant : Tenant::query()->findOrFail($tenant);
        $teamFk = config('permission.team_foreign_key', 'tenant_id');

        $roles = $user->roles()
            ->wherePivot($teamFk, $tenant->id)
            ->pluck('name')
            ->values()
            ->all();

        return $roles;
    }
}
