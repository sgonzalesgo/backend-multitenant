<?php
//
//namespace App\Repositories\Administration;
//
//use App\Models\Administration\Role;
//use App\Models\Administration\User;
//use App\Models\Administration\Tenant;
//use Illuminate\Contracts\Pagination\LengthAwarePaginator;
//use Illuminate\Database\Eloquent\Collection;
//use Illuminate\Support\Arr;
//use Illuminate\Support\Facades\DB;
//use Spatie\Permission\PermissionRegistrar;
//
//class RoleRepository
//{
//    public function __construct(
//        protected ?AuditLogRepository $audit = null
//    ) {}
//
//    protected function audit(): AuditLogRepository
//    {
//        return $this->audit ??= app(AuditLogRepository::class);
//    }
//
//    // ===== CRUD (roles por tenant) =====
//    public function list(array $filters = []): LengthAwarePaginator
//    {
//        $rawQ    = trim((string) Arr::get($filters, 'q', ''));
//        $sort    = Arr::get($filters, 'sort', 'name');
//        $dir     = strtolower((string) Arr::get($filters, 'dir', 'asc')) === 'desc' ? 'desc' : 'asc';
//        $perPage = max(1, min((int) Arr::get($filters, 'per_page', 15), 100));
//
//        if (! in_array($sort, ['name', 'guard_name', 'created_at', 'updated_at'], true)) {
//            $sort = 'name';
//        }
//
//        $global = '';
//        $name = '';
//        $tenantName = '';
//        $userEmail = '';
//        $createdAtInput = '';
//
//        if ($rawQ !== '') {
//            $decoded = json_decode($rawQ, true);
//
//            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
//                $global         = trim((string) Arr::get($decoded, 'global', ''));
//                $name           = trim((string) Arr::get($decoded, 'columns.name', ''));
//                $tenantName     = trim((string) Arr::get($decoded, 'columns.tenant', ''));
//                $userEmail      = trim((string) Arr::get($decoded, 'columns.user_email', ''));
//                $createdAtInput = trim((string) Arr::get($decoded, 'columns.created_at', ''));
//            } else {
//                $global = $rawQ;
//            }
//        }
//
//        $tenant = Tenant::current();
//        $teamFk = config('permission.team_foreign_key', 'tenant_id');
//
//        if ($tenant) {
//            app(PermissionRegistrar::class)->setPermissionsTeamId($tenant->id);
//        }
//
//        return Role::query()
//            ->with([
//                'tenant:id,name',
//                'users' => function ($query) use ($tenant, $teamFk) {
//                    $query->select('users.id', 'users.name', 'users.email', 'users.avatar');
//
//                    if ($tenant) {
//                        $query->wherePivot($teamFk, $tenant->id);
//                    }
//                },
//                'permissions:id,name,guard_name',
//            ])
//            ->when($tenant, function ($query) use ($tenant, $teamFk) {
//                $query->where(function ($q) use ($tenant, $teamFk) {
//                    $q->where("roles.$teamFk", $tenant->id)
//                        ->orWhereNull("roles.$teamFk");
//                });
//            })
//            ->when($global !== '', function ($query) use ($global) {
//                $query->where(function ($query) use ($global) {
//                    $query->where('roles.name', 'ilike', "%{$global}%")
//                        ->orWhereHas('tenant', function ($q) use ($global) {
//                            $q->where('name', 'ilike', "%{$global}%");
//                        })
//                        ->orWhereHas('users', function ($q) use ($global) {
//                            $q->where('users.email', 'ilike', "%{$global}%");
//                        });
//                });
//            })
//            ->when($name !== '', function ($query) use ($name) {
//                $query->where('roles.name', 'ilike', "%{$name}%");
//            })
//            ->when($tenantName !== '', function ($query) use ($tenantName) {
//                $query->whereHas('tenant', function ($q) use ($tenantName) {
//                    $q->where('name', 'ilike', "%{$tenantName}%");
//                });
//            })
//            ->when($userEmail !== '', function ($query) use ($userEmail, $tenant, $teamFk) {
//                $query->whereHas('users', function ($q) use ($userEmail, $tenant, $teamFk) {
//                    $q->where('users.email', 'ilike', "%{$userEmail}%");
//
//                    if ($tenant) {
//                        $q->wherePivot($teamFk, $tenant->id);
//                    }
//                });
//            })
//            ->when($createdAtInput !== '', function ($query) use ($createdAtInput) {
//                $query->whereDate('roles.created_at', $createdAtInput);
//            })
//            ->orderBy("roles.$sort", $dir)
//            ->paginate($perPage);
//    }
//    public function all(int|string|null $tenantId = null): Collection
//    {
//        $q = Role::query();
//        if ($tenantId) $q->where('tenant_id', $tenantId);
//        return $q->orderBy('name')->get();
//
//    }
//
//    public function findOrFail(int|string $id): Role
//    {
//        return Role::query()->findOrFail($id);
//
//    }
//
//    public function create(array $data): Role
//    {
//        $guard = Arr::get($data, 'guard_name', config('auth.defaults.guard', 'api'));
//
//        return DB::transaction(function () use ($data, $guard) {
//            $role = Role::create([
//                'name'       => $data['name'],
//                'guard_name' => $guard,
//                'tenant_id'  => $data['tenant_id'],
//            ]);
//
//            app(PermissionRegistrar::class)->forgetCachedPermissions();
//
//            // Audit
//            $this->audit()->log(
//                actor: auth()->user(),
//                event: 'Rol creado',
//                subject: $role,
//                description: __('audit.roles.created'),
//                changes: ['old' => null, 'new' => Arr::only($role->toArray(), ['id','name','guard_name','tenant_id'])],
//                tenantId: $role->tenant_id
//            );
//
//            return $role;
//        });
//    }
//
//    public function update(Role $role, array $data): Role
//    {
//        return DB::transaction(function () use ($role, $data) {
//            $old = Arr::only($role->getOriginal(), ['name','guard_name','tenant_id']);
//
//            // en general no se cambia tenant_id de un rol existente (pero lo soportamos si viene)
//            $role->fill(Arr::only($data, ['name','guard_name']));
//            if (array_key_exists('tenant_id', $data)) {
//                $role->tenant_id = $data['tenant_id'];
//            }
//            $role->save();
//
//            app(PermissionRegistrar::class)->forgetCachedPermissions();
//
//            $new = Arr::only($role->fresh()->toArray(), ['name','guard_name','tenant_id']);
//
//            // Audit
//            $this->audit()->log(
//                actor: auth()->user(),
//                event: 'Rol actualizado',
//                subject: $role,
//                description: __('audit.roles.updated'),
//                changes: ['old' => $old, 'new' => $new],
//                tenantId: $role->tenant_id
//            );
//
//            return $role->refresh();
//        });
//    }
//
//    public function delete(Role $role): void
//    {
//        DB::transaction(function () use ($role) {
//            $snapshot = Arr::only($role->toArray(), ['id','name','guard_name','tenant_id']);
//
//            $role->delete();
//            app(PermissionRegistrar::class)->forgetCachedPermissions();
//
//            // Audit
//            $this->audit()->log(
//                actor: auth()->user(),
//                event: 'Rol eliminado',
//                subject: ['type' => Role::class, 'id' => $snapshot['id']],
//                description: __('audit.roles.deleted'),
//                changes: ['old' => $snapshot, 'new' => null],
//                tenantId: $snapshot['tenant_id']
//            );
//        });
//    }
//
//    // ===== Permisos del rol (globales) =====
//
//    public function permissions(Role|int|string $role): Collection
//    {
//        $role = $role instanceof Role ? $role : Role::query()->findOrFail($role);
//        return$role->permissions()->orderBy('name')->get();
//    }
//
//    /** Sync total de permisos del rol (IDs). */
//    public function syncPermissionsByIds(Role|int|string $role, array $permissionIds): Role
//    {
//        $role = $role instanceof Role ? $role : Role::query()->findOrFail($role);
//
//        DB::transaction(function () use ($role, $permissionIds) {
//            // Snapshot anterior
//            $before = $role->permissions()->pluck('id')->values()->all();
//
//            $role->syncPermissions($permissionIds);
//            app(PermissionRegistrar::class)->forgetCachedPermissions();
//
//            // Snapshot nuevo
//            $after = $role->permissions()->pluck('id')->values()->all();
//
//            // Audit
//            $this->audit()->log(
//                actor: auth()->user(),
//                event: 'Permisos asignados',
//                subject: $role,
//                description: __('audit.roles.permissions.sync'),
//                changes: [
//                    'old' => ['permission_ids' => $before],
//                    'new' => ['permission_ids' => $after],
//                ],
//                tenantId: $role->tenant_id,
//                meta: ['role_id' => $role->id]
//            );
//        });
//
//        return $role->load('permissions');
//    }
//
//    // ===== Sync de roles del usuario en tenant (IDs) =====
//
//    /**
//     * Reemplaza completamente los roles del usuario en $tenantId.
//     * Verifica que TODOS los roles enviados pertenezcan a ese tenant.
//     */
//    public function syncUserRolesInTenant(User|int|string $user, Tenant|int|string $tenant, array $roleIds): array
//    {
//        $user   = $user   instanceof User   ? $user   : User::query()->findOrFail($user);
//        $tenant = $tenant instanceof Tenant ? $tenant : Tenant::query()->findOrFail($tenant);
//
//        // Validar que los roles pertenecen al mismo tenant
//        $count = Role::query()->whereIn('id', $roleIds)->where('tenant_id', $tenant->id)->count();
//        if ($count !== count($roleIds)) {
//            abort(422, __('validation/administration/assign_roles.custom.roles.*.tenant_mismatch')
//                ?? 'One or more roles do not belong to the specified tenant.');
//        }
//
//        return DB::transaction(function () use ($user, $tenant, $roleIds) {
//            $registrar = app(PermissionRegistrar::class);
//            $registrar->setPermissionsTeamId($tenant->id);
//
//            // Roles actuales del user en este tenant (antes)
//            $teamFk = config('permission.team_foreign_key', 'tenant_id');
//            $before = $user->roles()
//                ->wherePivot($teamFk, $tenant->id)
//                ->pluck('id')->values()->all();
//
//            // Sync
//            $user->syncRoles($roleIds);
//            $registrar->forgetCachedPermissions();
//
//            // Después
//            $after = $user->roles()
//                ->wherePivot($teamFk, $tenant->id)
//                ->pluck('id')->values()->all();
//
//            // Audit
//            $this->audit()->log(
//                actor: auth()->user(),
//                event: 'Roles asignados',
//                subject: $user,
//                description: __('audit.users.roles.sync_in_tenant'),
//                changes: [
//                    'old' => ['role_ids' => $before],
//                    'new' => ['role_ids' => $after],
//                ],
//                tenantId: $tenant->id,
//                meta: ['tenant_id' => $tenant->id, 'user_id' => $user->id]
//            );
//
//            return $after;
//        });
//    }
//}

//--------------------------------------------------
//namespace App\Repositories\Administration;
//
//use App\Models\Administration\Role;
//use App\Models\Administration\User;
//use App\Models\Administration\Tenant;
//use Illuminate\Contracts\Pagination\LengthAwarePaginator;
//use Illuminate\Database\Eloquent\Collection;
//use Illuminate\Support\Arr;
//use Illuminate\Support\Facades\DB;
//use Spatie\Permission\PermissionRegistrar;
//
//class RoleRepository
//{
//    public function __construct(
//        protected ?AuditLogRepository $audit = null
//    )
//    {
//    }
//
//    protected function audit(): AuditLogRepository
//    {
//        return $this->audit ??= app(AuditLogRepository::class);
//    }
//
//    /**
//     * Resuelve el tenant actual.
//     * Prioridad:
//     * 1) Tenant::current() (Spatie multitenancy)
//     * 2) tenant_id guardado en el access token actual
//     * 3) null
//     */
//    protected function resolveCurrentTenantId(): ?string
//    {
//        if ($current = Tenant::current()) {
//            return (string)$current->id;
//        }
//
//        $user = auth()->user();
//
//        if (!$user || !method_exists($user, 'token')) {
//            return null;
//        }
//
//        $token = $user->token();
//
//        if (!$token || empty($token->tenant_id)) {
//            return null;
//        }
//
//        return (string)$token->tenant_id;
//    }
//
//    /**
//     * Aplica el contexto de permisos al tenant actual.
//     */
//    protected function applyPermissionTeamScope(?string $tenantId): void
//    {
//        $registrar = app(PermissionRegistrar::class);
//        $registrar->setPermissionsTeamId($tenantId);
//        $registrar->forgetCachedPermissions();
//    }
//
//    // ===== CRUD =====
//    public function list(array $filters = []): LengthAwarePaginator
//    {
//        $rawQ = trim((string)Arr::get($filters, 'q', ''));
//        $sort = Arr::get($filters, 'sort', 'name');
//        $dir = strtolower((string)Arr::get($filters, 'dir', 'asc')) === 'desc' ? 'desc' : 'asc';
//        $perPage = max(1, min((int)Arr::get($filters, 'per_page', 15), 100));
//
//        if (!in_array($sort, ['name', 'guard_name', 'created_at', 'updated_at'], true)) {
//            $sort = 'name';
//        }
//
//        $global = '';
//        $name = '';
//        $tenantName = '';
//        $userEmail = '';
//        $createdAtInput = '';
//
//        if ($rawQ !== '') {
//            $decoded = json_decode($rawQ, true);
//
//            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
//                $global = trim((string)Arr::get($decoded, 'global', ''));
//                $name = trim((string)Arr::get($decoded, 'columns.name', ''));
//                $tenantName = trim((string)Arr::get($decoded, 'columns.tenant', ''));
//                $userEmail = trim((string)Arr::get($decoded, 'columns.user_email', ''));
//                $createdAtInput = trim((string)Arr::get($decoded, 'columns.created_at', ''));
//            } else {
//                $global = $rawQ;
//            }
//        }
//
//        $tenantId = $this->resolveCurrentTenantId();
//        $teamFk = config('permission.team_foreign_key', 'tenant_id');
//
//        $this->applyPermissionTeamScope($tenantId);
//
//        return Role::query()
//            ->with([
//                'tenant:id,name',
//                'users' => function ($query) use ($tenantId, $teamFk) {
//                    $query->select('users.id', 'users.name', 'users.email', 'users.avatar');
//
//                    if ($tenantId) {
//                        $query->wherePivot($teamFk, $tenantId);
//                    }
//                },
//                'permissions:id,name,guard_name',
//            ])
//            ->when($tenantId, function ($query) use ($tenantId, $teamFk) {
//                // explícito: roles del tenant actual + roles globales
//                $query->where(function ($q) use ($tenantId, $teamFk) {
//                    $q->where("roles.$teamFk", $tenantId)
//                        ->orWhereNull("roles.$teamFk");
//                });
//            })
//            ->when($global !== '', function ($query) use ($global, $tenantId, $teamFk) {
//                $query->where(function ($query) use ($global, $tenantId, $teamFk) {
//                    $query->where('roles.name', 'ilike', "%{$global}%")
//                        ->orWhereHas('tenant', function ($q) use ($global) {
//                            $q->where('name', 'ilike', "%{$global}%");
//                        })
//                        ->orWhereHas('users', function ($q) use ($global, $tenantId, $teamFk) {
//                            $q->where('users.email', 'ilike', "%{$global}%");
//
//                            if ($tenantId) {
//                                $q->where('model_has_roles.' . $teamFk, $tenantId);
//                            }
//                        });
//                });
//            })
//            ->when($name !== '', function ($query) use ($name) {
//                $query->where('roles.name', 'ilike', "%{$name}%");
//            })
//            ->when($tenantName !== '', function ($query) use ($tenantName) {
//                $query->whereHas('tenant', function ($q) use ($tenantName) {
//                    $q->where('name', 'ilike', "%{$tenantName}%");
//                });
//            })
//            ->when($userEmail !== '', function ($query) use ($userEmail, $tenantId, $teamFk) {
//                $query->whereHas('users', function ($q) use ($userEmail, $tenantId, $teamFk) {
//                    $q->where('users.email', 'ilike', "%{$userEmail}%");
//
//                    if ($tenantId) {
//                        $q->where('model_has_roles.' . $teamFk, $tenantId);
//                    }
//                });
//            })
//            ->when($createdAtInput !== '', function ($query) use ($createdAtInput) {
//                $query->whereDate('roles.created_at', $createdAtInput);
//            })
//            ->orderBy("roles.$sort", $dir)
//            ->paginate($perPage);
//    }
//
//    public function all(int|string|null $tenantId = null): Collection
//    {
//        $tenantId = $tenantId ? (string)$tenantId : $this->resolveCurrentTenantId();
//
//        return Role::query()
//            ->when($tenantId, function ($q) use ($tenantId) {
//                $q->where(function ($query) use ($tenantId) {
//                    $query->where('tenant_id', $tenantId)
//                        ->orWhereNull('tenant_id');
//                });
//            })
//            ->orderBy('name')
//            ->get();
//    }
//
//    public function findOrFail(int|string $id): Role
//    {
//        return Role::query()->findOrFail($id);
//    }
//
//    public function create(array $data): Role
//    {
//        $guard = Arr::get($data, 'guard_name', config('auth.defaults.guard', 'api'));
//        $tenantId = Arr::get($data, 'tenant_id', $this->resolveCurrentTenantId());
//
//        return DB::transaction(function () use ($data, $guard, $tenantId) {
//            $role = Role::create([
//                'name' => $data['name'],
//                'guard_name' => $guard,
//                'tenant_id' => $tenantId, // null = global, valor = tenant role
//            ]);
//
//            app(PermissionRegistrar::class)->forgetCachedPermissions();
//
//            $this->audit()->log(
//                actor: auth()->user(),
//                event: 'Rol creado',
//                subject: $role,
//                description: __('audit.roles.created'),
//                changes: ['old' => null, 'new' => Arr::only($role->toArray(), ['id', 'name', 'guard_name', 'tenant_id'])],
//                tenantId: $role->tenant_id
//            );
//
//            return $role;
//        });
//    }
//
//    public function update(Role $role, array $data): Role
//    {
//        return DB::transaction(function () use ($role, $data) {
//            $old = Arr::only($role->getOriginal(), ['name', 'guard_name', 'tenant_id']);
//
//            $role->fill(Arr::only($data, ['name', 'guard_name']));
//
//            // solo cambia tenant_id si te lo mandan explícitamente
//            if (array_key_exists('tenant_id', $data)) {
//                $role->tenant_id = $data['tenant_id'];
//            }
//
//            $role->save();
//
//            app(PermissionRegistrar::class)->forgetCachedPermissions();
//
//            $new = Arr::only($role->fresh()->toArray(), ['name', 'guard_name', 'tenant_id']);
//
//            $this->audit()->log(
//                actor: auth()->user(),
//                event: 'Rol actualizado',
//                subject: $role,
//                description: __('audit.roles.updated'),
//                changes: ['old' => $old, 'new' => $new],
//                tenantId: $role->tenant_id
//            );
//
//            return $role->refresh();
//        });
//    }
//
//    public function delete(Role $role): void
//    {
//        DB::transaction(function () use ($role) {
//            $snapshot = Arr::only($role->toArray(), ['id', 'name', 'guard_name', 'tenant_id']);
//
//            $role->delete();
//            app(PermissionRegistrar::class)->forgetCachedPermissions();
//
//            $this->audit()->log(
//                actor: auth()->user(),
//                event: 'Rol eliminado',
//                subject: ['type' => Role::class, 'id' => $snapshot['id']],
//                description: __('audit.roles.deleted'),
//                changes: ['old' => $snapshot, 'new' => null],
//                tenantId: $snapshot['tenant_id']
//            );
//        });
//    }
//
//    // ===== Permisos del rol =====
//
//    public function permissions(Role|int|string $role): Collection
//    {
//        $role = $role instanceof Role ? $role : Role::query()->findOrFail($role);
//        return $role->permissions()->orderBy('name')->get();
//    }
//
//    public function syncPermissionsByIds(Role|int|string $role, array $permissionIds): Role
//    {
//        $role = $role instanceof Role ? $role : Role::query()->findOrFail($role);
//
//        DB::transaction(function () use ($role, $permissionIds) {
//            $before = $role->permissions()->pluck('id')->values()->all();
//
//            $role->syncPermissions($permissionIds);
//            app(PermissionRegistrar::class)->forgetCachedPermissions();
//
//            $after = $role->permissions()->pluck('id')->values()->all();
//
//            $this->audit()->log(
//                actor: auth()->user(),
//                event: 'Permisos asignados',
//                subject: $role,
//                description: __('audit.roles.permissions.sync'),
//                changes: [
//                    'old' => ['permission_ids' => $before],
//                    'new' => ['permission_ids' => $after],
//                ],
//                tenantId: $role->tenant_id,
//                meta: ['role_id' => $role->id]
//            );
//        });
//
//        return $role->load('permissions');
//    }
//
//    // ===== Roles del usuario en tenant =====
//
//    /**
//     * Reemplaza completamente los roles del usuario SOLO en el tenant indicado.
//     */
//    public function syncUserRolesInTenant(User|int|string $user, Tenant|int|string $tenant, array $roleIds): array
//    {
//        $user = $user instanceof User ? $user : User::query()->findOrFail($user);
//        $tenant = $tenant instanceof Tenant ? $tenant : Tenant::query()->findOrFail($tenant);
//
//        $tenantId = (string)$tenant->id;
//        $teamFk = config('permission.team_foreign_key', 'tenant_id');
//
//        // validar que todos los roles pertenecen a ese tenant
//        $count = Role::query()
//            ->whereIn('id', $roleIds)
//            ->where('tenant_id', $tenantId)
//            ->count();
//
//        if ($count !== count($roleIds)) {
//            abort(422, __('validation/administration/assign_roles.custom.roles.*.tenant_mismatch')
//                ?? 'One or more roles do not belong to the specified tenant.');
//        }
//
//        return DB::transaction(function () use ($user, $tenantId, $roleIds, $teamFk) {
//            $registrar = app(PermissionRegistrar::class);
//            $registrar->setPermissionsTeamId($tenantId);
//            $registrar->forgetCachedPermissions();
//
//            $before = $user->roles()
//                ->wherePivot($teamFk, $tenantId)
//                ->pluck('roles.id')
//                ->values()
//                ->all();
//
//            // ✅ borrar SOLO roles del tenant actual en model_has_roles
//            DB::table(config('permission.table_names.model_has_roles', 'model_has_roles'))
//                ->where('model_type', User::class)
//                ->where('model_id', $user->getKey())
//                ->where($teamFk, $tenantId)
//                ->delete();
//
//            // ✅ asignar nuevos roles en el tenant actual
//            foreach ($roleIds as $roleId) {
//                $role = Role::query()->findOrFail($roleId);
//
//                $registrar->setPermissionsTeamId($tenantId);
//                $user->assignRole($role);
//            }
//
//            $registrar->forgetCachedPermissions();
//
//            $after = $user->roles()
//                ->wherePivot($teamFk, $tenantId)
//                ->pluck('roles.id')
//                ->values()
//                ->all();
//
//            $this->audit()->log(
//                actor: auth()->user(),
//                event: 'Roles asignados',
//                subject: $user,
//                description: __('audit.users.roles.sync_in_tenant'),
//                changes: [
//                    'old' => ['role_ids' => $before],
//                    'new' => ['role_ids' => $after],
//                ],
//                tenantId: $tenantId,
//                meta: ['tenant_id' => $tenantId, 'user_id' => $user->id]
//            );
//
//            return $after;
//        });
//    }
//}

// ------------------------------------------- nueva version


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
    )
    {
    }

    protected function audit(): AuditLogRepository
    {
        return $this->audit ??= app(AuditLogRepository::class);
    }

    /**
     * Resuelve el tenant actual.
     * Prioridad:
     * 1) Tenant::current() (Spatie multitenancy)
     * 2) tenant_id guardado en el access token actual
     * 3) null
     */
    protected function resolveCurrentTenantId(): ?string
    {
        if ($current = Tenant::current()) {
            return (string)$current->id;
        }

        $user = auth()->user();

        if (!$user || !method_exists($user, 'token')) {
            return null;
        }

        $token = $user->token();

        if (!$token || empty($token->tenant_id)) {
            return null;
        }

        return (string)$token->tenant_id;
    }

    /**
     * Aplica el contexto de permisos al tenant actual.
     */
    protected function applyPermissionTeamScope(?string $tenantId): void
    {
        $registrar = app(PermissionRegistrar::class);
        $registrar->setPermissionsTeamId($tenantId);
        $registrar->forgetCachedPermissions();
    }

    // ===== CRUD =====

    public function list(array $filters = []): LengthAwarePaginator
    {
        $rawQ = trim((string)Arr::get($filters, 'q', ''));
        $sort = Arr::get($filters, 'sort', 'name');
        $dir = strtolower((string)Arr::get($filters, 'dir', 'asc')) === 'desc' ? 'desc' : 'asc';
        $perPage = max(1, min((int)Arr::get($filters, 'per_page', 15), 100));

        if (!in_array($sort, ['name', 'guard_name', 'created_at', 'updated_at'], true)) {
            $sort = 'name';
        }

        $global = '';
        $name = '';
        $tenantName = '';
        $userEmail = '';
        $createdAtInput = '';

        if ($rawQ !== '') {
            $decoded = json_decode($rawQ, true);

            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $global = trim((string)Arr::get($decoded, 'global', ''));
                $name = trim((string)Arr::get($decoded, 'columns.name', ''));
                $tenantName = trim((string)Arr::get($decoded, 'columns.tenant', ''));
                $userEmail = trim((string)Arr::get($decoded, 'columns.user_email', ''));
                $createdAtInput = trim((string)Arr::get($decoded, 'columns.created_at', ''));
            } else {
                $global = $rawQ;
            }
        }

        $tenantId = $this->resolveCurrentTenantId();
        $teamFk = config('permission.team_foreign_key', 'tenant_id');

        $this->applyPermissionTeamScope($tenantId);

        return Role::query()
            ->with([
                'tenant:id,name',
                'users' => function ($query) use ($tenantId, $teamFk) {
                    $query->select('users.id', 'users.name', 'users.email', 'users.avatar');

                    if ($tenantId) {
                        $query->wherePivot($teamFk, $tenantId);
                    }
                },
                'permissions:id,name,guard_name',
            ])
            ->when($tenantId, function ($query) use ($tenantId, $teamFk) {
                $query->where(function ($q) use ($tenantId, $teamFk) {
                    $q->where("roles.$teamFk", $tenantId)
                        ->orWhereNull("roles.$teamFk");
                });
            })
            ->when($global !== '', function ($query) use ($global, $tenantId, $teamFk) {
                $query->where(function ($query) use ($global, $tenantId, $teamFk) {
                    $query->where('roles.name', 'ilike', "%{$global}%")
                        ->orWhereHas('tenant', function ($q) use ($global) {
                            $q->where('name', 'ilike', "%{$global}%");
                        })
                        ->orWhereHas('users', function ($q) use ($global, $tenantId, $teamFk) {
                            $q->where('users.email', 'ilike', "%{$global}%");

                            if ($tenantId) {
                                $q->where('model_has_roles.' . $teamFk, $tenantId);
                            }
                        });
                });
            })
            ->when($name !== '', function ($query) use ($name) {
                $query->where('roles.name', 'ilike', "%{$name}%");
            })
            ->when($tenantName !== '', function ($query) use ($tenantName) {
                $query->whereHas('tenant', function ($q) use ($tenantName) {
                    $q->where('name', 'ilike', "%{$tenantName}%");
                });
            })
            ->when($userEmail !== '', function ($query) use ($userEmail, $tenantId, $teamFk) {
                $query->whereHas('users', function ($q) use ($userEmail, $tenantId, $teamFk) {
                    $q->where('users.email', 'ilike', "%{$userEmail}%");

                    if ($tenantId) {
                        $q->where('model_has_roles.' . $teamFk, $tenantId);
                    }
                });
            })
            ->when($createdAtInput !== '', function ($query) use ($createdAtInput) {
                $query->whereDate('roles.created_at', $createdAtInput);
            })
            ->orderBy("roles.$sort", $dir)
            ->paginate($perPage);
    }

    public function all(int|string|null $tenantId = null): Collection
    {
        $tenantId = $tenantId ? (string)$tenantId : $this->resolveCurrentTenantId();

        return Role::query()
            ->when($tenantId, function ($q) use ($tenantId) {
                $q->where(function ($query) use ($tenantId) {
                    $query->where('tenant_id', $tenantId)
                        ->orWhereNull('tenant_id');
                });
            })
            ->orderBy('name')
            ->get();
    }

    public function findOrFail(int|string $id): Role
    {
        return Role::query()->findOrFail($id);
    }

    public function create(array $data): Role
    {
        $guard = Arr::get($data, 'guard_name', config('auth.defaults.guard', 'api'));
        $tenantId = Arr::get($data, 'tenant_id', $this->resolveCurrentTenantId());

        return DB::transaction(function () use ($data, $guard, $tenantId) {
            $role = Role::create([
                'name' => $data['name'],
                'guard_name' => $guard,
                'tenant_id' => $tenantId, // null = global, valor = tenant role
            ]);

            app(PermissionRegistrar::class)->forgetCachedPermissions();

            $this->audit()->log(
                actor: auth()->user(),
                event: 'Rol creado',
                subject: $role,
                description: __('audit.roles.created'),
                changes: ['old' => null, 'new' => Arr::only($role->toArray(), ['id', 'name', 'guard_name', 'tenant_id'])],
                tenantId: $role->tenant_id
            );

            return $role;
        });
    }

    public function update(Role $role, array $data): Role
    {
        return DB::transaction(function () use ($role, $data) {
            $old = Arr::only($role->getOriginal(), ['name', 'guard_name', 'tenant_id']);

            $role->fill(Arr::only($data, ['name', 'guard_name']));

            if (array_key_exists('tenant_id', $data)) {
                $role->tenant_id = $data['tenant_id'];
            }

            $role->save();

            app(PermissionRegistrar::class)->forgetCachedPermissions();

            $new = Arr::only($role->fresh()->toArray(), ['name', 'guard_name', 'tenant_id']);

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
            $snapshot = Arr::only($role->toArray(), ['id', 'name', 'guard_name', 'tenant_id']);

            $role->delete();
            app(PermissionRegistrar::class)->forgetCachedPermissions();

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

    // ===== Permisos del rol =====

    public function permissions(Role|int|string $role): Collection
    {
        $role = $role instanceof Role ? $role : Role::query()->findOrFail($role);
        return $role->permissions()->orderBy('name')->get();
    }

    public function syncPermissionsByIds(Role|int|string $role, array $permissionIds): Role
    {
        $role = $role instanceof Role ? $role : Role::query()->findOrFail($role);

        DB::transaction(function () use ($role, $permissionIds) {
            $before = $role->permissions()->pluck('id')->values()->all();

            $role->syncPermissions($permissionIds);
            app(PermissionRegistrar::class)->forgetCachedPermissions();

            $after = $role->permissions()->pluck('id')->values()->all();

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

    // ===== Roles del usuario en tenant =====

    /**
     * Si roles viene vacío, elimina todos los roles del usuario en ese tenant.
     */
    public function syncUserRolesInTenant(User|int|string $user, array $roleIds): array
    {
        $user = $user instanceof User ? $user : User::query()->findOrFail($user);

        $tenantId = $this->resolveCurrentTenantId();

        if (! $tenantId) {
            abort(400, 'No current tenant resolved.');
        }

        $teamFk = config('permission.team_foreign_key', 'tenant_id');

        return DB::transaction(function () use ($user, $tenantId, $roleIds, $teamFk) {
            $registrar = app(PermissionRegistrar::class);
            $registrar->setPermissionsTeamId($tenantId);
            $registrar->forgetCachedPermissions();

            // Antes
            $before = $user->roles()
                ->wherePivot($teamFk, $tenantId)
                ->pluck('roles.id')
                ->values()
                ->all();

            // 1) Borrar TODOS los roles del usuario en este tenant
            DB::table(config('permission.table_names.model_has_roles', 'model_has_roles'))
                ->where('model_type', User::class)
                ->where('model_id', $user->getKey())
                ->where($teamFk, $tenantId)
                ->delete();

            // 2) Si no vienen roles, termina aquí: usuario queda sin roles en este tenant
            if (empty($roleIds)) {
                $registrar->forgetCachedPermissions();

                $this->audit()->log(
                    actor: auth()->user(),
                    event: 'Roles asignados',
                    subject: $user,
                    description: __('audit.users.roles.sync_in_tenant'),
                    changes: [
                        'old' => ['role_ids' => $before],
                        'new' => ['role_ids' => []],
                    ],
                    tenantId: $tenantId,
                    meta: ['tenant_id' => $tenantId, 'user_id' => $user->id]
                );

                return [];
            }

            // 3) Validar que todos los roles pertenecen al tenant actual
            $count = Role::query()
                ->whereIn('id', $roleIds)
                ->where('tenant_id', $tenantId)
                ->count();

            if ($count !== count($roleIds)) {
                abort(422, __('validation/administration/assign_roles.custom.roles.*.tenant_mismatch')
                    ?? 'One or more roles do not belong to the current tenant.');
            }

            // 4) Asignar nuevos roles en el tenant actual
            foreach ($roleIds as $roleId) {
                $role = Role::query()
                    ->where('tenant_id', $tenantId)
                    ->findOrFail($roleId);

                $registrar->setPermissionsTeamId($tenantId);
                $user->assignRole($role);
            }

            $registrar->forgetCachedPermissions();

            // Después
            $after = $user->roles()
                ->wherePivot($teamFk, $tenantId)
                ->pluck('roles.id')
                ->values()
                ->all();

            $this->audit()->log(
                actor: auth()->user(),
                event: 'Roles asignados',
                subject: $user,
                description: __('audit.users.roles.sync_in_tenant'),
                changes: [
                    'old' => ['role_ids' => $before],
                    'new' => ['role_ids' => $after],
                ],
                tenantId: $tenantId,
                meta: ['tenant_id' => $tenantId, 'user_id' => $user->id]
            );

            return $after;
        });
    }
}
