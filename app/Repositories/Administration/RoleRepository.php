<?php

namespace App\Repositories\Administration;

use App\Models\Administration\Role;
use App\Models\Administration\User;
use App\Models\Administration\Tenant;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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

            return $role->refresh();
        });
    }

    public function delete(Role $role): void
    {
        DB::transaction(function () use ($role) {
            $snapshot = Arr::only($role->toArray(), ['id', 'name', 'guard_name', 'tenant_id']);

            $role->delete();
            app(PermissionRegistrar::class)->forgetCachedPermissions();
        });
    }

    // ===== Permisos del rol =====
    public function permissions(Role|int|string $role): Collection
    {
        $role = $role instanceof Role ? $role : Role::query()->findOrFail($role);
        return $role->permissions()->orderBy('name')->get();
    }

    /**
     * Sincroniza completamente los permisos de un rol.
     *
     * Qué hace:
     * - toma el snapshot actual de permisos del rol usando nombres
     * - reemplaza los permisos existentes por los enviados
     * - limpia caché de Spatie Permission
     * - registra auditoría con valores legibles para frontend
     *
     * Formato de auditoría:
     * - changes.old.permission_names
     * - changes.new.permission_names
     * - meta.added_permission_names
     * - meta.removed_permission_names
     */
    public function syncPermissionsByIds(Role|int|string $role, array $permissionIds): Role
    {
        $role = $role instanceof Role ? $role : Role::query()->findOrFail($role);

        DB::transaction(function () use ($role, $permissionIds) {
            // Snapshot anterior por nombres
            $before = $role->permissions()
                ->pluck('permissions.name')
                ->map(fn ($name) => (string) $name)
                ->values()
                ->all();

            // Sincroniza completamente los permisos del rol
            $role->syncPermissions($permissionIds);

            // Limpia caché de permisos
            app(PermissionRegistrar::class)->forgetCachedPermissions();

            // Snapshot posterior por nombres
            $after = $role->permissions()
                ->pluck('permissions.name')
                ->map(fn ($name) => (string) $name)
                ->values()
                ->all();

            // Diff semántico
            $added = array_values(array_diff($after, $before));
            $removed = array_values(array_diff($before, $after));

            // Audit alineado con frontend
            $this->audit()->log(
                actor: auth()->user(),
                event: 'Permisos sincronizados al rol',
                subject: $role,
                description: __('audit.roles.permissions.sync'),
                changes: [
                    'old' => [
                        'permission_names' => $before,
                    ],
                    'new' => [
                        'permission_names' => $after,
                    ],
                ],
                tenantId: $role->tenant_id,
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
     * Sincroniza completamente los roles de un usuario dentro del tenant actual.
     *
     * Qué hace:
     * - resuelve el tenant actual
     * - toma el snapshot de roles actuales del usuario en ese tenant usando nombres
     * - elimina todos los roles actuales del usuario en ese tenant
     * - valida que los nuevos roles pertenezcan al tenant actual
     * - asigna los nuevos roles
     * - limpia caché de Spatie Permission
     * - registra auditoría con valores legibles para frontend
     *
     * Comportamiento:
     * - si $roleIds viene vacío, elimina todos los roles del usuario en el tenant actual
     *
     * Formato de auditoría:
     * - changes.old.role_names
     * - changes.new.role_names
     * - meta.added_role_names
     * - meta.removed_role_names
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

            // Snapshot anterior por nombres
            $before = $user->roles()
                ->wherePivot($teamFk, $tenantId)
                ->pluck('roles.name')
                ->map(fn ($name) => (string) $name)
                ->values()
                ->all();

            // Elimina todos los roles actuales del usuario SOLO en este tenant
            DB::table(config('permission.table_names.model_has_roles', 'model_has_roles'))
                ->where('model_type', User::class)
                ->where('model_id', $user->getKey())
                ->where($teamFk, $tenantId)
                ->delete();

            // Si no vienen roles, el usuario queda sin roles en este tenant
            if (empty($roleIds)) {
                $registrar->forgetCachedPermissions();

                $after = [];
                $added = [];
                $removed = array_values($before);

                $this->audit()->log(
                    actor: auth()->user(),
                    event: 'Roles sincronizados al usuario',
                    subject: $user,
                    description: __('audit.users.roles.sync_in_tenant'),
                    changes: [
                        'old' => [
                            'role_names' => $before,
                        ],
                        'new' => [
                            'role_names' => $after,
                        ],
                    ],
                    tenantId: $tenantId,
                    meta: [
                        'tenant_id' => $tenantId,
                        'user_name' => $user->name,
                        'user_email' => $user->email,
                        'added_role_names' => $added,
                        'removed_role_names' => $removed,
                        'assigned_count' => 0,
                    ]
                );

                return $after;
            }

            // Validar que todos los roles pertenezcan al tenant actual
            $count = Role::query()
                ->whereIn('id', $roleIds)
                ->where('tenant_id', $tenantId)
                ->count();

            if ($count !== count($roleIds)) {
                abort(
                    422,
                    __('validation/administration/assign_roles.custom.roles.*.tenant_mismatch')
                    ?? 'One or more roles do not belong to the current tenant.'
                );
            }

            // Asignar nuevos roles en el tenant actual
            foreach ($roleIds as $roleId) {
                $role = Role::query()
                    ->where('tenant_id', $tenantId)
                    ->findOrFail($roleId);

                $registrar->setPermissionsTeamId($tenantId);
                $user->assignRole($role);
            }

            $registrar->forgetCachedPermissions();

            // Snapshot posterior por nombres
            $after = $user->roles()
                ->wherePivot($teamFk, $tenantId)
                ->pluck('roles.name')
                ->map(fn ($name) => (string) $name)
                ->values()
                ->all();

            // Diff semántico
            $added = array_values(array_diff($after, $before));
            $removed = array_values(array_diff($before, $after));

            $this->audit()->log(
                actor: auth()->user(),
                event: 'Roles sincronizados al usuario',
                subject: $user,
                description: __('audit.users.roles.sync_in_tenant'),
                changes: [
                    'old' => [
                        'role_names' => $before,
                    ],
                    'new' => [
                        'role_names' => $after,
                    ],
                ],
                tenantId: $tenantId,
                meta: [
                    'tenant_id' => $tenantId,
                    'user_name' => $user->name,
                    'user_email' => $user->email,
                    'added_role_names' => $added,
                    'removed_role_names' => $removed,
                    'assigned_count' => count($after),
                ]
            );

            return $after;
        });
    }

}
