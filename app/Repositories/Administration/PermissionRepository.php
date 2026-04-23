<?php

namespace App\Repositories\Administration;

use App\Models\Administration\Permission;
use App\Models\Administration\Role;
use App\Models\Administration\Tenant;
use App\Models\Administration\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

class PermissionRepository
{
    public function __construct(
        protected ?AuditLogRepository $audit = null
    )
    {
    }

    protected function audit(): AuditLogRepository
    {
        return $this->audit ??= app(AuditLogRepository::class);
    }

    /**
     * Fuente de verdad del tenant actual.
     */
    protected function currentTenant(): ?Tenant
    {
        return Tenant::current();
    }

    /**
     * Aplica el team scope actual a Spatie Permission.
     */
    protected function applyPermissionTeamScope(?string $tenantId): void
    {
        $registrar = app(PermissionRegistrar::class);
        $registrar->setPermissionsTeamId($tenantId);
        $registrar->forgetCachedPermissions();
    }

    /**
     * Devuelve el tenant actual o corta si no pudo resolverse.
     * @throws AuthorizationException
     */
    protected function currentTenantOrFail(): Tenant
    {
        $tenant = $this->currentTenant();

        if (!$tenant) {
            throw new AuthorizationException(__('messages.tenants.current_not_resolved'));
        }

        return $tenant;
    }

    /**
     * Resuelve un rol respetando el tenant efectivo actual.
     *
     * Reglas:
     * - si hay tenant actual: se permiten roles globales o del tenant actual
     * - si no hay tenant actual: solo se permiten roles globales
     */
    protected function resolveTenantAwareRole(Role|int|string $role): Role
    {
        $roleModel = $role instanceof Role
            ? $role
            : Role::query()->findOrFail($role);

        $tenant = $this->currentTenant();
        $teamFk = config('permission.team_foreign_key', 'tenant_id');
        $roleTenantId = $roleModel->getAttribute($teamFk);

        if (!$tenant) {
            abort_unless(is_null($roleTenantId), 404);

            return $roleModel;
        }

        abort_unless(
            is_null($roleTenantId) || (string)$roleTenantId === (string)$tenant->id,
            404
        );

        return $roleModel;
    }

    /**
     * Pagina permisos (globales).
     */
    public function list(array $filters = []): LengthAwarePaginator
    {
        $rawQ = trim((string)Arr::get($filters, 'q', ''));
        $sort = Arr::get($filters, 'sort', 'name');
        $dir = strtolower((string)Arr::get($filters, 'dir', 'asc')) === 'desc' ? 'desc' : 'asc';
        $perPage = max(1, min((int)Arr::get($filters, 'per_page', 15), 600));

        if (!in_array($sort, ['name', 'guard_name', 'created_at', 'updated_at'], true)) {
            $sort = 'name';
        }

        $global = '';
        $name = '';
        $guardName = '';
        $createdAtInput = '';

        if ($rawQ !== '') {
            $decoded = json_decode($rawQ, true);

            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $global = trim((string)Arr::get($decoded, 'global', ''));
                $name = trim((string)Arr::get($decoded, 'columns.name', ''));
                $guardName = trim((string)Arr::get($decoded, 'columns.guard_name', ''));
                $createdAtInput = trim((string)Arr::get($decoded, 'columns.created_at', ''));
            } else {
                $global = $rawQ;
            }
        }

        $tenant = $this->currentTenant();
        $tenantId = $tenant?->id ? (string)$tenant->id : null;
        $teamFk = config('permission.team_foreign_key', 'tenant_id');

        $this->applyPermissionTeamScope($tenantId);

        $paginator = Permission::query()
            ->with([
                'roles' => function ($query) use ($tenantId, $teamFk) {
                    $query->select('roles.id', 'roles.name', 'roles.guard_name', "roles.$teamFk");

                    if ($tenantId) {
                        $query->where(function ($q) use ($tenantId, $teamFk) {
                            $q->where("roles.$teamFk", $tenantId)
                                ->orWhereNull("roles.$teamFk");
                        });
                    } else {
                        $query->whereNull("roles.$teamFk");
                    }
                },

                'users' => function ($query) {
                    $query->select('users.id', 'users.name', 'users.email', 'users.avatar');
                },

                'modelPermissionAssignments' => function ($query) use ($tenantId, $teamFk) {
                    $query->select('permission_id', 'model_type', 'model_id', $teamFk);

                    if ($tenantId) {
                        $query->where(function ($q) use ($tenantId, $teamFk) {
                            $q->where($teamFk, $tenantId)
                                ->orWhereNull($teamFk);
                        });
                    } else {
                        $query->whereNull($teamFk);
                    }
                },
            ])
            ->when($global !== '', function ($query) use ($global) {
                $query->where(function ($query) use ($global) {
                    $query->where('name', 'ilike', "%{$global}%")
                        ->orWhere('guard_name', 'ilike', "%{$global}%");
                });
            })
            ->when($name !== '', function ($query) use ($name) {
                $query->where('name', 'ilike', "%{$name}%");
            })
            ->when($guardName !== '', function ($query) use ($guardName) {
                $query->where('guard_name', 'ilike', "%{$guardName}%");
            })
            ->when($createdAtInput !== '', function ($query) use ($createdAtInput) {
                $query->whereDate('created_at', $createdAtInput);
            })
            ->orderBy($sort, $dir)
            ->paginate($perPage);

        $paginator->getCollection()->transform(function (Permission $permission) {
            $permission->setAttribute(
                'assigned_models',
                $permission->modelPermissionAssignments
                    ->map(function ($assignment) {
                        return [
                            'model_type' => $assignment->model_type,
                            'model' => class_basename($assignment->model_type),
                            'model_id' => $assignment->model_id,
                        ];
                    })
                    ->values()
            );

            unset($permission->modelPermissionAssignments);

            return $permission;
        });

        return $paginator;
    }

    /**
     * Todos los permisos (globales).
     */
    public function all(): Collection
    {
        return Permission::query()
            ->orderBy('name')
            ->get();
    }

    /**
     * Buscar por ID.
     */
    public function findOrFail(int|string $id): Permission
    {
        return Permission::query()->findOrFail($id);
    }

    /**
     * Crear permiso global.
     */
    public function create(array $data): Permission
    {
        $guard = Arr::get($data, 'guard_name', config('auth.defaults.guard', 'api'));
        $tenantId = $this->currentTenant()?->id;

        return DB::transaction(function () use ($data, $guard, $tenantId) {
            $perm = Permission::create([
                'name' => $data['name'],
                'guard_name' => $guard,
            ]);

            app(PermissionRegistrar::class)->forgetCachedPermissions();

            return $perm;
        });
    }

    /**
     * Actualizar permiso global.
     */
    public function update(Permission $permission, array $data): Permission
    {
        $tenantId = $this->currentTenant()?->id;

        return DB::transaction(function () use ($permission, $data, $tenantId) {
            $old = Arr::only($permission->getOriginal(), ['name', 'guard_name']);

            $permission->fill(Arr::only($data, ['name', 'guard_name']));
            $permission->save();

            app(PermissionRegistrar::class)->forgetCachedPermissions();

            return $permission->refresh();
        });
    }

    /**
     * Eliminar permiso global.
     */
    public function delete(Permission $permission): void
    {
        $tenantId = $this->currentTenant()?->id;

        DB::transaction(function () use ($permission, $tenantId) {
            $snapshot = Arr::only($permission->toArray(), ['id', 'name', 'guard_name']);

            $permission->delete();
            app(PermissionRegistrar::class)->forgetCachedPermissions();
        });
    }

    // -------------------------------------------------
    // Rol <-> Permisos
    // -------------------------------------------------

    public function permissionsForRole(Role|int|string $role): Collection
    {
        $role = $this->resolveTenantAwareRole($role);

        return $role->permissions()
            ->orderBy('name')
            ->get();
    }

    public function attachPermissionsToRoleByIds(Role|int|string $role, array $permissionIds): Role
    {
        $role = $this->resolveTenantAwareRole($role);
        $tenantId = $this->currentTenant()?->id;

        DB::transaction(function () use ($role, $permissionIds, $tenantId) {
            $role->givePermissionTo($permissionIds);
            app(PermissionRegistrar::class)->forgetCachedPermissions();

            $this->audit()->log(
                actor: auth()->user(),
                event: 'Permisos asignados',
                subject: $role,
                description: __('audit.roles_permissions.attach'),
                changes: ['old' => null, 'new' => ['permission_ids' => array_values($permissionIds)]],
                tenantId: $tenantId,
                meta: ['role_id' => $role->id, 'attached_count' => count($permissionIds)]
            );
        });

        return $role->load('permissions');
    }

    public function revokePermissionsFromRoleByIds(Role|int|string $role, array $permissionIds): Role
    {
        $role = $this->resolveTenantAwareRole($role);
        $tenantId = $this->currentTenant()?->id;

        DB::transaction(function () use ($role, $permissionIds, $tenantId) {
            foreach ($permissionIds as $pid) {
                $role->revokePermissionTo($pid);
            }

            app(PermissionRegistrar::class)->forgetCachedPermissions();

            $this->audit()->log(
                actor: auth()->user(),
                event: 'Permisos revocados',
                subject: $role,
                description: __('audit.roles_permissions.revoke'),
                changes: ['old' => ['permission_ids' => array_values($permissionIds)], 'new' => null],
                tenantId: $tenantId,
                meta: ['role_id' => $role->id, 'revoked_count' => count($permissionIds)]
            );
        });

        return $role->load('permissions');
    }

    /**
     * Sincroniza completamente los permisos de un rol y registra auditoría usando
     * nombres legibles en lugar de IDs.
     *
     * Auditoría:
     * - changes.old.permission_names
     * - changes.new.permission_names
     * - meta.role_name
     * - meta.added_permission_names
     * - meta.removed_permission_names
     * - meta.assigned_count
     */
    public function syncRolePermissionsByIds(Role|int|string $role, array $permissionIds): Role
    {
        $role = $this->resolveTenantAwareRole($role);
        $tenantId = $this->currentTenant()?->id;

        DB::transaction(function () use ($role, $permissionIds, $tenantId) {
            // Snapshot anterior por nombres
            $before = $role->permissions()
                ->pluck('permissions.name')
                ->map(fn ($name) => (string) $name)
                ->values()
                ->all();

            // Sincroniza permisos del rol
            $role->syncPermissions($permissionIds);

            // Limpia caché de Spatie Permission
            app(PermissionRegistrar::class)->forgetCachedPermissions();

            // Refresca el modelo para evitar relaciones cacheadas
            $role->refresh();

            // Snapshot posterior por nombres
            $after = $role->permissions()
                ->pluck('permissions.name')
                ->map(fn ($name) => (string) $name)
                ->values()
                ->all();

            // Diferencias semánticas
            $added = array_values(array_diff($after, $before));
            $removed = array_values(array_diff($before, $after));

            $this->audit()->log(
                actor: auth()->user(),
                event: __('audit.permissions.sync_permission'),
                subject: $role,
                description: __('audit.roles_permissions.sync'),
                changes: [
                    'old' => [
                        __('audit.permissions.permission_names') => $before,
                    ],
                    'new' => [
                        __('audit.permissions.permission_names') => $after,
                    ],
                ],
                tenantId: $tenantId,
                meta: [
                    'role_name' => $role->name,
                    'added_permission_names' => $added,
                    'removed_permission_names' => $removed,
                    'assigned_count' => count($after),
                ]
            );
        });

        return $role->load('permissions');
    }


    /**
     * Sincroniza completamente los permisos directos de un usuario dentro del tenant actual.
     *
     * Qué hace:
     * - resuelve el tenant actual
     * - aplica el team scope de Spatie Permission
     * - toma el snapshot actual de permisos directos usando nombres
     * - reemplaza los permisos directos por los enviados
     * - limpia caché de Spatie Permission
     * - vuelve a consultar permisos usando nombres
     * - registra auditoría legible para frontend
     *
     * Auditoría:
     * - changes.old.permission_names
     * - changes.new.permission_names
     * - meta.user_name
     * - meta.user_email
     * - meta.added_permission_names
     * - meta.removed_permission_names
     * - meta.assigned_count
     */
    public function syncModelPermissionsByIds(string $modelId, array $permissionIds): User
    {
        return DB::transaction(function () use ($modelId, $permissionIds) {
            $tenant = $this->currentTenant();
            $tenantId = $tenant?->id ? (string) $tenant->id : null;

            $this->applyPermissionTeamScope($tenantId);

            /** @var User $model */
            $model = User::query()->findOrFail($modelId);

            // Limpia relaciones cargadas por si el modelo viene con cache en memoria
            $model->unsetRelation('roles');
            $model->unsetRelation('permissions');

            // Snapshot anterior por nombres
            $before = $model->permissions()
                ->pluck('permissions.name')
                ->map(fn ($name) => (string) $name)
                ->values()
                ->all();

            // Sincroniza permisos directos del usuario
            $model->syncPermissions($permissionIds);

            // Limpia caché de permisos
            app(PermissionRegistrar::class)->forgetCachedPermissions();

            // Limpia relaciones nuevamente para forzar recarga real
            $model->unsetRelation('roles');
            $model->unsetRelation('permissions');
            $model->refresh();

            // Snapshot posterior por nombres
            $after = $model->permissions()
                ->pluck('permissions.name')
                ->map(fn ($name) => (string) $name)
                ->values()
                ->all();

            // Diff semántico
            $added = array_values(array_diff($after, $before));
            $removed = array_values(array_diff($before, $after));

            $this->audit()->log(
                actor: auth()->user(),
                event: __('audit.permissions.sync_permission_model'),
                subject: $model,
                description: __('audit.model_permissions.sync'),
                changes: [
                    'old' => [
                        __('audit.permissions.permission_names') => $before,
                    ],
                    'new' => [
                        __('audit.permissions.permission_names') => $after,
                    ],
                ],
                tenantId: $tenantId,
                meta: [
                    'model_type' => User::class,
                    'user_name' => $model->name,
                    'user_email' => $model->email,
                    'added_permission_names' => $added,
                    'removed_permission_names' => $removed,
                    'assigned_count' => count($after),
                ]
            );

            return $model->load('permissions');
        });
    }

    /**
     * Permisos efectivos del usuario en el tenant actual.
     */
    public function currentTenantUserPermissions(User $user): array
    {
        $tenant = $this->currentTenantOrFail();

        $registrar = app(PermissionRegistrar::class);
        $registrar->setPermissionsTeamId($tenant->id);
        $registrar->forgetCachedPermissions();

        $user->unsetRelation('roles');
        $user->unsetRelation('permissions');

        return $user->getAllPermissions()
            ->pluck('name')
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Roles del usuario en el tenant actual.
     */
    public function currentTenantUserRoles(User $user): array
    {
        $tenant = $this->currentTenantOrFail();
        $teamFk = config('permission.team_foreign_key', 'tenant_id');

        return $user->roles()
            ->wherePivot($teamFk, $tenant->id)
            ->pluck('name')
            ->values()
            ->all();
    }

    /**
     * Compatibilidad hacia atrás.
     * Evitar usar en flujos tenant-aware normales.
     */
    public function userPermissionsInTenant(User $user, Tenant|int|string $tenant): array
    {
        $tenant = $tenant instanceof Tenant ? $tenant : Tenant::query()->findOrFail($tenant);

        $registrar = app(PermissionRegistrar::class);
        $registrar->setPermissionsTeamId($tenant->id);
        $registrar->forgetCachedPermissions();

        $user->unsetRelation('roles');
        $user->unsetRelation('permissions');

        return $user->getAllPermissions()
            ->pluck('name')
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Compatibilidad hacia atrás.
     * Evitar usar en flujos tenant-aware normales.
     */
    public function userRolesInTenant(User $user, Tenant|int|string $tenant): array
    {
        $tenant = $tenant instanceof Tenant ? $tenant : Tenant::query()->findOrFail($tenant);
        $teamFk = config('permission.team_foreign_key', 'tenant_id');

        return $user->roles()
            ->wherePivot($teamFk, $tenant->id)
            ->pluck('name')
            ->values()
            ->all();
    }
}
