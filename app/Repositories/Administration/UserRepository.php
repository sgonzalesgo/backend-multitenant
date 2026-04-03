<?php
//--------------------------------- nueva version

//
//namespace App\Repositories\Administration;
//
//use App\Http\Requests\Administration\User\RegisterRequest;
//use App\Models\Administration\Tenant;
//use App\Models\Administration\User;
//use Illuminate\Contracts\Pagination\LengthAwarePaginator;
//use Illuminate\Database\Eloquent\Builder;
//use Illuminate\Database\Eloquent\Collection;
//use Illuminate\Support\Arr;
//use Illuminate\Support\Facades\DB;
//use Illuminate\Support\Facades\Hash;
//use Illuminate\Support\Facades\Log;
//use Spatie\Permission\PermissionRegistrar;
//use Illuminate\Http\UploadedFile;
//use Illuminate\Support\Facades\Storage;
//use App\Http\Requests\Administration\User\StoreUserRequest;
//use App\Http\Requests\Administration\User\UpdateUserRequest;
//
//class UserRepository
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
//     * 1) Tenant::current()
//     * 2) tenant_id del access token actual
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
//    /**
//     * Aplica el scoping de visibilidad por tenant.
//     *
//     * Regla (esto ya no va porque puedo tener usuarios con roles en otros tenants):
//     * - si hay tenant actual:
//     *   - usuarios con roles en ese tenant
//     *   - o usuarios con permisos directos en ese tenant
//     *   - o usuarios sin roles ni permisos directos en ningún tenant
//     * - si no hay tenant actual:
//     *   - solo usuarios sin roles ni permisos directos
//     */
//    protected function applyTenantVisibilityScope(Builder $query, ?string $tenantId): Builder
//    {
//        $teamFk = config('permission.team_foreign_key', 'tenant_id');
//        $usersTable = (new User())->getTable();
//        $modelHasRolesTable = config('permission.table_names.model_has_roles', 'model_has_roles');
//        $modelHasPermissionsTable = config('permission.table_names.model_has_permissions', 'model_has_permissions');
//
//        if ($tenantId) {
//            $query->where(function ($qb) use (
//                $usersTable,
//                $tenantId,
//                $teamFk,
//                $modelHasRolesTable,
//                $modelHasPermissionsTable
//            ) {
//                $qb
//                    // Ya tiene roles en este tenant
//                    ->whereExists(function ($sub) use ($usersTable, $tenantId, $teamFk, $modelHasRolesTable) {
//                        $sub->from($modelHasRolesTable . ' as mhr')
//                            ->where('mhr.model_type', User::class)
//                            ->whereColumn('mhr.model_id', "{$usersTable}.id")
//                            ->where("mhr.{$teamFk}", $tenantId);
//                    })
//
//                    // Ya tiene permisos directos en este tenant
//                    ->orWhereExists(function ($sub) use ($usersTable, $tenantId, $teamFk, $modelHasPermissionsTable) {
//                        $sub->from($modelHasPermissionsTable . ' as mhp')
//                            ->where('mhp.model_type', User::class)
//                            ->whereColumn('mhp.model_id', "{$usersTable}.id")
//                            ->where("mhp.{$teamFk}", $tenantId);
//                    })
//
//                    // NO tiene nada asignado todavía en este tenant
//                    ->orWhere(function ($subQ) use (
//                        $usersTable,
//                        $tenantId,
//                        $teamFk,
//                        $modelHasRolesTable,
//                        $modelHasPermissionsTable
//                    ) {
//                        $subQ->whereNotExists(function ($sub) use ($usersTable, $tenantId, $teamFk, $modelHasRolesTable) {
//                            $sub->from($modelHasRolesTable . ' as mhr2')
//                                ->where('mhr2.model_type', User::class)
//                                ->whereColumn('mhr2.model_id', "{$usersTable}.id")
//                                ->where("mhr2.{$teamFk}", $tenantId);
//                        })->whereNotExists(function ($sub) use ($usersTable, $tenantId, $teamFk, $modelHasPermissionsTable) {
//                            $sub->from($modelHasPermissionsTable . ' as mhp2')
//                                ->where('mhp2.model_type', User::class)
//                                ->whereColumn('mhp2.model_id', "{$usersTable}.id")
//                                ->where("mhp2.{$teamFk}", $tenantId);
//                        });
//                    });
//            });
//        } else {
//            $query->where(function ($qb) use ($usersTable, $modelHasRolesTable, $modelHasPermissionsTable) {
//                $qb->whereNotExists(function ($sub) use ($usersTable, $modelHasRolesTable) {
//                    $sub->from($modelHasRolesTable . ' as mhr2')
//                        ->where('mhr2.model_type', User::class)
//                        ->whereColumn('mhr2.model_id', "{$usersTable}.id");
//                })->whereNotExists(function ($sub) use ($usersTable, $modelHasPermissionsTable) {
//                    $sub->from($modelHasPermissionsTable . ' as mhp2')
//                        ->where('mhp2.model_type', User::class)
//                        ->whereColumn('mhp2.model_id', "{$usersTable}.id");
//                });
//            });
//        }
//
//        return $query;
//    }
//
//    /**
//     * Lista con filtros tipo RoleRepository + scoping por current tenant.
//     */
//    public function list(array $filters = []): LengthAwarePaginator
//    {
//        $rawQ = trim((string)Arr::get($filters, 'q', ''));
//        $sort = Arr::get($filters, 'sort', 'name');
//        $dir = strtolower((string)Arr::get($filters, 'dir', 'asc')) === 'desc' ? 'desc' : 'asc';
//        $perPage = max(1, min((int)Arr::get($filters, 'per_page', 15), 100));
//
//        if (!in_array($sort, ['name', 'email', 'is_active', 'created_at', 'updated_at'], true)) {
//            $sort = 'name';
//        }
//
//        $global = '';
//        $name = '';
//        $email = '';
//        $roleName = '';
//        $permissionName = '';
//        $createdAtInput = '';
//        $isActiveInput = '';
//
//        if ($rawQ !== '') {
//            $decoded = json_decode($rawQ, true);
//
//            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
//                $global = trim((string)Arr::get($decoded, 'global', ''));
//                $name = trim((string)Arr::get($decoded, 'columns.name', ''));
//                $email = trim((string)Arr::get($decoded, 'columns.email', ''));
//                $roleName = trim((string)Arr::get($decoded, 'columns.role', ''));
//                $permissionName = trim((string)Arr::get($decoded, 'columns.permission', ''));
//                $createdAtInput = trim((string)Arr::get($decoded, 'columns.created_at', ''));
//                $isActiveInput = trim((string)Arr::get($decoded, 'columns.is_active', ''));
//            } else {
//                $global = $rawQ;
//            }
//        }
//
//        $tenantId = $this->resolveCurrentTenantId();
//        $teamFk = config('permission.team_foreign_key', 'tenant_id');
//        $usersTable = (new User())->getTable();
//
//        $this->applyPermissionTeamScope($tenantId);
//
//        $query = User::query()
//            ->with([
//                'roles' => function ($query) use ($tenantId, $teamFk) {
//                    $query->select('roles.id', 'roles.name', 'roles.guard_name', 'roles.tenant_id');
//
//                    if ($tenantId) {
//                        $query->where(function ($sub) use ($tenantId, $teamFk) {
//                            $sub->where("roles.$teamFk", $tenantId)
//                                ->orWhereNull("roles.$teamFk");
//                        })->wherePivot($teamFk, $tenantId);
//                    }
//                },
//                'permissions' => function ($query) use ($tenantId, $teamFk) {
//                    $query->select('permissions.id', 'permissions.name', 'permissions.guard_name');
//
//                    if ($tenantId) {
//                        $query->where('model_has_permissions.' . $teamFk, $tenantId);
//                    }
//                },
//            ]);
//
//        $query = $this->applyTenantVisibilityScope($query, $tenantId);
//
//        $query
//            ->when($global !== '', function ($query) use ($global, $tenantId, $teamFk, $usersTable) {
//                $query->where(function ($sub) use ($global, $tenantId, $teamFk, $usersTable) {
//                    $sub->where("{$usersTable}.name", 'ilike', "%{$global}%")
//                        ->orWhere("{$usersTable}.email", 'ilike', "%{$global}%")
//                        ->orWhereHas('roles', function ($rq) use ($global, $tenantId, $teamFk) {
//                            $rq->where('roles.name', 'ilike', "%{$global}%");
//
//                            if ($tenantId) {
//                                $rq->where('model_has_roles.' . $teamFk, $tenantId);
//                            }
//                        })
//                        ->orWhereHas('permissions', function ($pq) use ($global, $tenantId, $teamFk) {
//                            $pq->where('permissions.name', 'ilike', "%{$global}%");
//
//                            if ($tenantId) {
//                                $pq->where('model_has_permissions.' . $teamFk, $tenantId);
//                            }
//                        });
//                });
//            })
//            ->when($name !== '', function ($query) use ($name, $usersTable) {
//                $query->where("{$usersTable}.name", 'ilike', "%{$name}%");
//            })
//            ->when($email !== '', function ($query) use ($email, $usersTable) {
//                $query->where("{$usersTable}.email", 'ilike', "%{$email}%");
//            })
//            ->when($roleName !== '', function ($query) use ($roleName, $tenantId, $teamFk) {
//                $query->whereHas('roles', function ($rq) use ($roleName, $tenantId, $teamFk) {
//                    $rq->where('roles.name', 'ilike', "%{$roleName}%");
//
//                    if ($tenantId) {
//                        $rq->where('model_has_roles.' . $teamFk, $tenantId);
//                    }
//                });
//            })
//            ->when($permissionName !== '', function ($query) use ($permissionName, $tenantId, $teamFk) {
//                $query->whereHas('permissions', function ($pq) use ($permissionName, $tenantId, $teamFk) {
//                    $pq->where('permissions.name', 'ilike', "%{$permissionName}%");
//
//                    if ($tenantId) {
//                        $pq->where('model_has_permissions.' . $teamFk, $tenantId);
//                    }
//                });
//            })
//            ->when($createdAtInput !== '', function ($query) use ($createdAtInput, $usersTable) {
//                $query->whereDate("{$usersTable}.created_at", $createdAtInput);
//            })
//            ->when($isActiveInput !== '', function ($query) use ($isActiveInput, $usersTable) {
//                $normalized = filter_var($isActiveInput, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
//
//                if ($normalized !== null) {
//                    $query->where("{$usersTable}.is_active", $normalized);
//                }
//            });
//
//        return $query
//            ->orderBy("{$usersTable}.{$sort}", $dir)
//            ->paginate($perPage);
//    }
//
//    public function all(): Collection
//    {
//        $tenantId = $this->resolveCurrentTenantId();
//        $teamFk = config('permission.team_foreign_key', 'tenant_id');
//
//        $this->applyPermissionTeamScope($tenantId);
//
//        $query = User::query()
//            ->with([
//                'roles' => function ($query) use ($tenantId, $teamFk) {
//                    $query->select('roles.id', 'roles.name', 'roles.guard_name', 'roles.tenant_id');
//
//                    if ($tenantId) {
//                        $query->where(function ($sub) use ($tenantId, $teamFk) {
//                            $sub->where("roles.$teamFk", $tenantId)
//                                ->orWhereNull("roles.$teamFk");
//                        })->wherePivot($teamFk, $tenantId);
//                    }
//                },
//                'permissions' => function ($query) use ($tenantId, $teamFk) {
//                    $query->select('permissions.id', 'permissions.name', 'permissions.guard_name');
//
//                    if ($tenantId) {
//                        $query->where('model_has_permissions.' . $teamFk, $tenantId);
//                    }
//                },
//            ]);
//
//        $query = $this->applyTenantVisibilityScope($query, $tenantId);
//
//        return $query
//            ->orderBy('name')
//            ->get();
//    }
//
//    public function findOrFail(string $id): User
//    {
//        $tenantId = $this->resolveCurrentTenantId();
//        $teamFk = config('permission.team_foreign_key', 'tenant_id');
//
//        $this->applyPermissionTeamScope($tenantId);
//
//        $query = User::query()
//            ->with([
//                'roles' => function ($query) use ($tenantId, $teamFk) {
//                    $query->select('roles.id', 'roles.name', 'roles.guard_name', 'roles.tenant_id');
//
//                    if ($tenantId) {
//                        $query->where(function ($sub) use ($tenantId, $teamFk) {
//                            $sub->where("roles.$teamFk", $tenantId)
//                                ->orWhereNull("roles.$teamFk");
//                        })->wherePivot($teamFk, $tenantId);
//                    }
//                },
//                'permissions' => function ($query) use ($tenantId, $teamFk) {
//                    $query->select('permissions.id', 'permissions.name', 'permissions.guard_name');
//
//                    if ($tenantId) {
//                        $query->where('model_has_permissions.' . $teamFk, $tenantId);
//                    }
//                },
//            ])
//            ->whereKey($id);
//
//        $query = $this->applyTenantVisibilityScope($query, $tenantId);
//
//        return $query->firstOrFail();
//    }
//
//    public function create(StoreUserRequest $req): User
//    {
//        return DB::transaction(function () use ($req) {
//            $data = $req->validated();
//
//            $avatarPath = null;
//            $avatarFile = $req->file('avatar');
//
//            if ($avatarFile instanceof UploadedFile) {
//                $avatarPath = $this->storeAvatar($avatarFile);
//            }
//
//            $user = new User();
//            $user->name = $data['name'];
//            $user->email = $data['email'];
//            $user->password = Hash::make($data['password']);
//            $user->avatar = $avatarPath;
//            $user->locale = $data['locale'] ?? app()->getLocale();
//            $user->is_active = true;
//            $user->email_verified_at = now();
//
//            $user->save();
//
//            $this->audit()->log(
//                actor: auth()->user(),
//                event: 'Usuario creado',
//                subject: $user,
//                description: __('audit.users.created'),
//                changes: [
//                    'old' => null,
//                    'new' => Arr::only($user->toArray(), ['id', 'name', 'email', 'avatar', 'locale', 'is_active']),
//                ],
//                tenantId: $this->resolveCurrentTenantId()
//            );
//
//            return $user;
//        });
//    }
//
//    public function update(User $user, UpdateUserRequest $req): User
//    {
//        return DB::transaction(function () use ($user, $req) {
//            $data = $req->validated();
//            $old = Arr::only($user->getOriginal(), ['name', 'email', 'locale', 'avatar', 'is_active']);
//
//            if (array_key_exists('name', $data)) {
//                $user->name = $data['name'];
//            }
//
//            if (array_key_exists('email', $data)) {
//                $user->email = $data['email'];
//            }
//
//            if (array_key_exists('locale', $data)) {
//                $user->locale = $data['locale'];
//            }
//
//            if (array_key_exists('is_active', $data)) {
//                $user->is_active = (bool) $data['is_active'];
//            }
//
//            if (!empty($data['password'])) {
//                $user->password = Hash::make($data['password']);
//            }
//
//            $avatarFile = $req->file('avatar');
//
//
//            if ($avatarFile instanceof UploadedFile) {
//                $user->avatar = $this->replaceAvatar($user->avatar, $avatarFile);
//            }
//
//            $user->save();
//            $fresh = $user->refresh();
//
//            $this->audit()->log(
//                actor: auth()->user(),
//                event: 'Usuario actualizado',
//                subject: $fresh,
//                description: __('audit.users.updated'),
//                changes: [
//                    'old' => $old,
//                    'new' => Arr::only($fresh->toArray(), ['name', 'email', 'locale', 'avatar', 'is_active']),
//                ],
//                tenantId: $this->resolveCurrentTenantId()
//            );
//
//            return $fresh;
//        });
//    }
//
//    public function delete(User $user): void
//    {
//        DB::transaction(function () use ($user) {
//            $snapshot = Arr::only($user->toArray(), ['id', 'name', 'email', 'locale', 'avatar', 'is_active']);
//
//            if (!empty($user->avatar)) {
//                $this->deleteAvatar($user->avatar);
//            }
//
//            $user->delete();
//
//            $this->audit()->log(
//                actor: auth()->user(),
//                event: 'Usuario eliminado',
//                subject: ['type' => User::class, 'id' => $snapshot['id']],
//                description: __('audit.users.deleted'),
//                changes: ['old' => $snapshot, 'new' => null],
//                tenantId: $this->resolveCurrentTenantId()
//            );
//        });
//    }
//
//    protected function issueTokenPayload(User $user): array
//    {
//        $tokenResult = $user->createToken('api');
//        $accessToken = $tokenResult->accessToken;
//        $expiresAt = optional($tokenResult->token->expires_at)?->toIso8601String();
//
//        return [
//            'user' => [
//                'id' => $user->id,
//                'name' => $user->name,
//                'email' => $user->email,
//                'avatar' => $user->avatar,
//                'locale' => $user->locale,
//            ],
//            'access_token' => $accessToken,
//            'expires_at' => $expiresAt,
//        ];
//    }
//
//    public function upsertFromSocialAccessToken(string $provider, string $accessToken, array $hints = []): User
//    {
//        $socialUser = \Laravel\Socialite\Facades\Socialite::driver($provider)
//            ->stateless()
//            ->userFromToken($accessToken);
//
//        $providerId = (string)$socialUser->getId();
//        $email = $socialUser->getEmail() ?: ($hints['email'] ?? null);
//        $name = $socialUser->getName() ?: ($hints['name'] ?? 'User');
//        $avatar = $socialUser->getAvatar() ?: ($hints['avatar'] ?? null);
//        $locale = $hints['locale'] ?? app()->getLocale();
//
//        $query = User::query();
//
//        if ($provider === 'google') {
//            $query->where('google_id', $providerId);
//        }
//
//        if ($provider === 'facebook') {
//            $query->where('facebook_id', $providerId);
//        }
//
//        $user = $query->first();
//
//        if (!$user && $email) {
//            $user = User::query()->where('email', $email)->first();
//        }
//
//        $created = !$user;
//
//        return DB::transaction(function () use ($user, $provider, $providerId, $email, $name, $avatar, $locale, $created) {
//            if (!$user) {
//                $user = new User();
//                $user->email = $email ?: "user_{$provider}_{$providerId}@example.local";
//                $user->password = Hash::make(\Str::random(40));
//            }
//
//            $user->name = $name ?: $user->name;
//            $user->avatar = $avatar ?: $user->avatar;
//            $user->locale = $locale ?: $user->locale;
//            $user->is_active = true;
//
//            if ($provider === 'google') {
//                $user->google_id = $providerId;
//            }
//
//            if ($provider === 'facebook') {
//                $user->facebook_id = $providerId;
//            }
//
//            if ($email && (!$user->email || str_ends_with($user->email, '@example.local'))) {
//                $user->email = $email;
//            }
//
//            $user->save();
//            $fresh = $user->refresh();
//
//            $this->audit()->log(
//                actor: auth()->user(),
//                event: 'users.social.upsert',
//                subject: $fresh,
//                description: $created ? __('audit.users.social.created') : __('audit.users.social.updated'),
//                changes: [
//                    'old' => null,
//                    'new' => Arr::only($fresh->toArray(), ['id', 'email', 'name', 'locale', 'is_active']),
//                ],
//                tenantId: $this->resolveCurrentTenantId(),
//                meta: [
//                    'provider' => $provider,
//                    'linked' => true,
//                ]
//            );
//
//            return $fresh;
//        });
//    }
//
//    private function storeAvatar(UploadedFile $file): string
//    {
//        return $file->store('users/avatars', 'public');
//    }
//
//    private function deleteAvatar(?string $path): void
//    {
//        if (!$path) {
//            return;
//        }
//
//        if (Storage::disk('public')->exists($path)) {
//            Storage::disk('public')->delete($path);
//        }
//    }
//    private function replaceAvatar(?string $currentPath, UploadedFile $file): string
//    {
//        $newPath = $this->storeAvatar($file);
//
//        if ($currentPath && $currentPath !== $newPath) {
//            $this->deleteAvatar($currentPath);
//        }
//
//        return $newPath;
//    }
//}


// ----------------------------- nueva version ------------------------------------------


namespace App\Repositories\Administration;

use App\Http\Requests\Administration\User\RegisterRequest;
use App\Http\Requests\Administration\User\StoreUserRequest;
use App\Http\Requests\Administration\User\UpdateUserRequest;
use App\Models\Administration\Tenant;
use App\Models\Administration\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Laravel\Socialite\Facades\Socialite;
use Spatie\Permission\PermissionRegistrar;

class UserRepository
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

    protected function applyPermissionTeamScope(?string $tenantId): void
    {
        $registrar = app(PermissionRegistrar::class);
        $registrar->setPermissionsTeamId($tenantId);
        $registrar->forgetCachedPermissions();
    }

    protected function applyTenantVisibilityScope(Builder $query, ?string $tenantId): Builder
    {
        $teamFk = config('permission.team_foreign_key', 'tenant_id');
        $usersTable = (new User())->getTable();
        $modelHasRolesTable = config('permission.table_names.model_has_roles', 'model_has_roles');
        $modelHasPermissionsTable = config('permission.table_names.model_has_permissions', 'model_has_permissions');

        if ($tenantId) {
            $query->where(function ($qb) use (
                $usersTable,
                $tenantId,
                $teamFk,
                $modelHasRolesTable,
                $modelHasPermissionsTable
            ) {
                $qb
                    ->whereExists(function ($sub) use ($usersTable, $tenantId, $teamFk, $modelHasRolesTable) {
                        $sub->from($modelHasRolesTable . ' as mhr')
                            ->where('mhr.model_type', User::class)
                            ->whereColumn('mhr.model_id', "{$usersTable}.id")
                            ->where("mhr.{$teamFk}", $tenantId);
                    })
                    ->orWhereExists(function ($sub) use ($usersTable, $tenantId, $teamFk, $modelHasPermissionsTable) {
                        $sub->from($modelHasPermissionsTable . ' as mhp')
                            ->where('mhp.model_type', User::class)
                            ->whereColumn('mhp.model_id', "{$usersTable}.id")
                            ->where("mhp.{$teamFk}", $tenantId);
                    })
                    ->orWhere(function ($subQ) use (
                        $usersTable,
                        $tenantId,
                        $teamFk,
                        $modelHasRolesTable,
                        $modelHasPermissionsTable
                    ) {
                        $subQ->whereNotExists(function ($sub) use ($usersTable, $tenantId, $teamFk, $modelHasRolesTable) {
                            $sub->from($modelHasRolesTable . ' as mhr2')
                                ->where('mhr2.model_type', User::class)
                                ->whereColumn('mhr2.model_id', "{$usersTable}.id")
                                ->where("mhr2.{$teamFk}", $tenantId);
                        })->whereNotExists(function ($sub) use ($usersTable, $tenantId, $teamFk, $modelHasPermissionsTable) {
                            $sub->from($modelHasPermissionsTable . ' as mhp2')
                                ->where('mhp2.model_type', User::class)
                                ->whereColumn('mhp2.model_id', "{$usersTable}.id")
                                ->where("mhp2.{$teamFk}", $tenantId);
                        });
                    });
            });
        } else {
            $query->where(function ($qb) use ($usersTable, $modelHasRolesTable, $modelHasPermissionsTable) {
                $qb->whereNotExists(function ($sub) use ($usersTable, $modelHasRolesTable) {
                    $sub->from($modelHasRolesTable . ' as mhr2')
                        ->where('mhr2.model_type', User::class)
                        ->whereColumn('mhr2.model_id', "{$usersTable}.id");
                })->whereNotExists(function ($sub) use ($usersTable, $modelHasPermissionsTable) {
                    $sub->from($modelHasPermissionsTable . ' as mhp2')
                        ->where('mhp2.model_type', User::class)
                        ->whereColumn('mhp2.model_id', "{$usersTable}.id");
                });
            });
        }

        return $query;
    }

    public function list(array $filters = []): LengthAwarePaginator
    {
        $rawQ = trim((string)Arr::get($filters, 'q', ''));
        $sort = Arr::get($filters, 'sort', 'name');
        $dir = strtolower((string)Arr::get($filters, 'dir', 'asc')) === 'desc' ? 'desc' : 'asc';
        $perPage = max(1, min((int)Arr::get($filters, 'per_page', 15), 100));

        if (!in_array($sort, ['name', 'email', 'status', 'created_at', 'updated_at'], true)) {
            $sort = 'name';
        }


        $global = '';
        $name = '';
        $email = '';
        $roleName = '';
        $permissionName = '';
        $createdAtInput = '';
        $statusInput = '';

        if ($rawQ !== '') {
            $decoded = json_decode($rawQ, true);

            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $global = trim((string)Arr::get($decoded, 'global', ''));
                $name = trim((string)Arr::get($decoded, 'columns.name', ''));
                $email = trim((string)Arr::get($decoded, 'columns.email', ''));
                $roleName = trim((string)Arr::get($decoded, 'columns.role', ''));
                $permissionName = trim((string)Arr::get($decoded, 'columns.permission', ''));
                $createdAtInput = trim((string)Arr::get($decoded, 'columns.created_at', ''));
                $statusInput = trim((string)Arr::get($decoded, 'columns.status', ''));
            } else {
                $global = $rawQ;
            }
        }

        $tenantId = $this->resolveCurrentTenantId();
        $teamFk = config('permission.team_foreign_key', 'tenant_id');
        $usersTable = (new User())->getTable();

        $this->applyPermissionTeamScope($tenantId);

        $query = User::query()
            ->with([
                'person:id,full_name,legal_id,legal_id_type,email,phone,photo,status',
                'roles' => function ($query) use ($tenantId, $teamFk) {
                    $query->select('roles.id', 'roles.name', 'roles.guard_name', 'roles.tenant_id');

                    if ($tenantId) {
                        $query->where(function ($sub) use ($tenantId, $teamFk) {
                            $sub->where("roles.$teamFk", $tenantId)
                                ->orWhereNull("roles.$teamFk");
                        })->wherePivot($teamFk, $tenantId);
                    }
                },
                'permissions' => function ($query) use ($tenantId, $teamFk) {
                    $query->select('permissions.id', 'permissions.name', 'permissions.guard_name');

                    if ($tenantId) {
                        $query->where('model_has_permissions.' . $teamFk, $tenantId);
                    }
                },
            ]);

        $query = $this->applyTenantVisibilityScope($query, $tenantId);

        $query
            ->when($global !== '', function ($query) use ($global, $tenantId, $teamFk, $usersTable) {
                $query->where(function ($sub) use ($global, $tenantId, $teamFk, $usersTable) {
                    $sub->where("{$usersTable}.name", 'ilike', "%{$global}%")
                        ->orWhere("{$usersTable}.email", 'ilike', "%{$global}%")
                        ->orWhere("{$usersTable}.status", 'ilike', "%{$global}%")
                        ->orWhereHas('person', function ($pq) use ($global) {
                            $pq->where('full_name', 'ilike', "%{$global}%")
                                ->orWhere('legal_id', 'ilike', "%{$global}%");
                        })
                        ->orWhereHas('roles', function ($rq) use ($global, $tenantId, $teamFk) {
                            $rq->where('roles.name', 'ilike', "%{$global}%");

                            if ($tenantId) {
                                $rq->where('model_has_roles.' . $teamFk, $tenantId);
                            }
                        })
                        ->orWhereHas('permissions', function ($pq) use ($global, $tenantId, $teamFk) {
                            $pq->where('permissions.name', 'ilike', "%{$global}%");

                            if ($tenantId) {
                                $pq->where('model_has_permissions.' . $teamFk, $tenantId);
                            }
                        });
                });
            })
            ->when($name !== '', function ($query) use ($name, $usersTable) {
                $query->where("{$usersTable}.name", 'ilike', "%{$name}%");
            })
            ->when($email !== '', function ($query) use ($email, $usersTable) {
                $query->where("{$usersTable}.email", 'ilike', "%{$email}%");
            })
            ->when($roleName !== '', function ($query) use ($roleName, $tenantId, $teamFk) {
                $query->whereHas('roles', function ($rq) use ($roleName, $tenantId, $teamFk) {
                    $rq->where('roles.name', 'ilike', "%{$roleName}%");

                    if ($tenantId) {
                        $rq->where('model_has_roles.' . $teamFk, $tenantId);
                    }
                });
            })
            ->when($permissionName !== '', function ($query) use ($permissionName, $tenantId, $teamFk) {
                $query->whereHas('permissions', function ($pq) use ($permissionName, $tenantId, $teamFk) {
                    $pq->where('permissions.name', 'ilike', "%{$permissionName}%");

                    if ($tenantId) {
                        $pq->where('model_has_permissions.' . $teamFk, $tenantId);
                    }
                });
            })
            ->when($createdAtInput !== '', function ($query) use ($createdAtInput, $usersTable) {
                $query->whereDate("{$usersTable}.created_at", $createdAtInput);
            })
            ->when($statusInput !== '', function ($query) use ($statusInput, $usersTable) {
                $query->where("{$usersTable}.status", 'ilike', "%{$statusInput}%");
            });

        return $query
            ->orderBy("{$usersTable}.{$sort}", $dir)
            ->paginate($perPage);
    }

    public function all(): Collection
    {
        $tenantId = $this->resolveCurrentTenantId();
        $teamFk = config('permission.team_foreign_key', 'tenant_id');

        $this->applyPermissionTeamScope($tenantId);

        $query = User::query()
            ->with([
                'person:id,full_name,legal_id,legal_id_type,email,phone,photo,status',
                'roles' => function ($query) use ($tenantId, $teamFk) {
                    $query->select('roles.id', 'roles.name', 'roles.guard_name', 'roles.tenant_id');

                    if ($tenantId) {
                        $query->where(function ($sub) use ($tenantId, $teamFk) {
                            $sub->where("roles.$teamFk", $tenantId)
                                ->orWhereNull("roles.$teamFk");
                        })->wherePivot($teamFk, $tenantId);
                    }
                },
                'permissions' => function ($query) use ($tenantId, $teamFk) {
                    $query->select('permissions.id', 'permissions.name', 'permissions.guard_name');

                    if ($tenantId) {
                        $query->where('model_has_permissions.' . $teamFk, $tenantId);
                    }
                },
            ]);

        $query = $this->applyTenantVisibilityScope($query, $tenantId);

        return $query->orderBy('name')->get();
    }

    public function findOrFail(string $id): User
    {
        $tenantId = $this->resolveCurrentTenantId();
        $teamFk = config('permission.team_foreign_key', 'tenant_id');

        $this->applyPermissionTeamScope($tenantId);

        $query = User::query()
            ->with([
                'person:id,full_name,legal_id,legal_id_type,email,phone,photo,status',
                'roles' => function ($query) use ($tenantId, $teamFk) {
                    $query->select('roles.id', 'roles.name', 'roles.guard_name', 'roles.tenant_id');

                    if ($tenantId) {
                        $query->where(function ($sub) use ($tenantId, $teamFk) {
                            $sub->where("roles.$teamFk", $tenantId)
                                ->orWhereNull("roles.$teamFk");
                        })->wherePivot($teamFk, $tenantId);
                    }
                },
                'permissions' => function ($query) use ($tenantId, $teamFk) {
                    $query->select('permissions.id', 'permissions.name', 'permissions.guard_name');

                    if ($tenantId) {
                        $query->where('model_has_permissions.' . $teamFk, $tenantId);
                    }
                },
            ])
            ->whereKey($id);

        $query = $this->applyTenantVisibilityScope($query, $tenantId);

        return $query->firstOrFail();
    }

    public function create(StoreUserRequest $req): User
    {
        return DB::transaction(function () use ($req) {
            $data = $req->validated();

            $avatarPath = null;
            $avatarFile = $req->file('avatar');

            if ($avatarFile instanceof UploadedFile) {
                $avatarPath = $this->storeAvatar($avatarFile);
            }

            $user = new User();
            $user->person_id = $data['person_id'] ?? null;
            $user->name = $data['name'];
            $user->email = $data['email'];
            $user->password = Hash::make($data['password']);
            $user->avatar = $avatarPath;
            $user->locale = $data['locale'] ?? app()->getLocale();
            $user->status = $data['status'] ?? 'active';
            $user->email_verified_at = now();
            $user->save();

            $fresh = $user->refresh()->load('person');

            return $fresh;
        });
    }

    public function update(User $user, UpdateUserRequest $req): User
    {
        return DB::transaction(function () use ($user, $req) {
            $data = $req->validated();
            $old = Arr::only($user->getOriginal(), ['person_id', 'name', 'email', 'locale', 'avatar', 'status']);

            if (array_key_exists('person_id', $data)) {
                $user->person_id = $data['person_id'];
            }

            if (array_key_exists('name', $data)) {
                $user->name = $data['name'];
            }

            if (array_key_exists('email', $data)) {
                $user->email = $data['email'];
            }

            if (array_key_exists('locale', $data)) {
                $user->locale = $data['locale'];
            }

            if (array_key_exists('status', $data)) {
                $user->status = $data['status'];
            }

            if (!empty($data['password'])) {
                $user->password = Hash::make($data['password']);
            }

            $avatarFile = $req->file('avatar');

            if ($avatarFile instanceof UploadedFile) {
                $user->avatar = $this->replaceAvatar($user->avatar, $avatarFile);
            }

            $user->save();

            return $user->refresh()->load('person');
        });
    }

    public function delete(User $user): void
    {
        DB::transaction(function () use ($user) {
            $snapshot = Arr::only($user->toArray(), ['id', 'person_id', 'name', 'email', 'locale', 'avatar', 'status']);

            if (!empty($user->avatar)) {
                $this->deleteAvatar($user->avatar);
            }

            $user->delete();
        });
    }

    public function register(array|RegisterRequest $data): array
    {
        $payload = $data instanceof RegisterRequest
            ? $data->validated()
            : $data;

        $user = DB::transaction(function () use ($payload) {
            $user = new User();
            $user->person_id = $payload['person_id'] ?? null;
            $user->name = $payload['name'];
            $user->email = $payload['email'];
            $user->password = Hash::make($payload['password']);

            if (($payload['avatar'] ?? null) instanceof UploadedFile) {
                $user->avatar = $this->storeAvatar($payload['avatar']);
            } else {
                $user->avatar = $payload['avatar'] ?? null;
            }

            $user->locale = $payload['locale'] ?? app()->getLocale();
            $user->status = 'inactive';
            $user->email_verified_at = null;
            $user->save();

            return $user;
        });

        app(EmailVerificationRepository::class)
            ->issueCode($user, 'verify_email', request()->ip(), request()->userAgent());

//        $this->audit()->log(
//            actor: auth()->user(),
//            event: __('audit.users.register'),
//            subject: $user,
//            description: __('audit.users.register'),
//            changes: ['old' => null, 'new' => ['id' => $user->id, 'email' => $user->email]],
//            tenantId: Tenant::current()?->id
//        );

        return $this->issueTokenPayload($user);
    }

    protected function issueTokenPayload(User $user): array
    {
        $tokenResult = $user->createToken('api');
        $accessToken = $tokenResult->accessToken;
        $expiresAt = optional($tokenResult->token->expires_at)?->toIso8601String();

        return [
            'user' => [
                'id' => $user->id,
                'person_id' => $user->person_id,
                'name' => $user->name,
                'email' => $user->email,
                'avatar' => $user->avatar,
                'locale' => $user->locale,
                'status' => $user->status,
            ],
            'access_token' => $accessToken,
            'expires_at' => $expiresAt,
        ];
    }

    public function upsertFromSocialAccessToken(string $provider, string $accessToken, array $hints = []): User
    {
        try {
            $socialUser = Socialite::driver($provider)
                ->stateless()
                ->userFromToken($accessToken);


        } catch (\Throwable $e) {
            report($e);

            abort(422, __('messages.auth.social_invalid_token'));
        }

        $providerId = (string)$socialUser->getId();
        $email = $socialUser->getEmail() ?: ($hints['email'] ?? null);
        $name = $socialUser->getName() ?: ($hints['name'] ?? 'User');
        $avatar = $socialUser->getAvatar() ?: ($hints['avatar'] ?? null);
        $locale = $hints['locale'] ?? app()->getLocale();

        $query = User::query();

        if ($provider === 'google') {
            $query->where('google_id', $providerId);
        }

        if ($provider === 'facebook') {
            $query->where('facebook_id', $providerId);
        }

        $user = $query->first();

        if (!$user && $email) {
            $user = User::query()->where('email', $email)->first();
        }

        $created = !$user;

        return DB::transaction(function () use ($user, $provider, $providerId, $email, $name, $avatar, $locale, $created) {
            if (!$user) {
                $user = new User();
                $user->email = $email ?: "user_{$provider}_{$providerId}@example.local";
                $user->password = Hash::make(\Str::random(40));
            }

            $user->name = $name ?: $user->name;
            $user->avatar = $avatar ?: $user->avatar;
            $user->locale = $locale ?: $user->locale;
            $user->status = 'active';

            if ($provider === 'google') {
                $user->google_id = $providerId;
            }

            if ($provider === 'facebook') {
                $user->facebook_id = $providerId;
            }

            if ($email && (!$user->email || str_ends_with($user->email, '@example.local'))) {
                $user->email = $email;
            }

            $user->save();
            $fresh = $user->refresh()->load('person');

            $this->audit()->log(
                actor: auth()->user(),
                event: __('audit.users.social.created') ,
                subject: $fresh,
                description: $created ? __('audit.users.social.created') : __('audit.users.social.updated'),
                changes: [
                    'old' => null,
                    'new' => Arr::only($fresh->toArray(), ['id', 'person_id', 'email', 'name', 'locale', 'status']),
                ],
                tenantId: $this->resolveCurrentTenantId(),
                meta: [
                    'provider' => $provider,
                    'linked' => true,
                ]
            );

            return $fresh;
        });
    }

    private function storeAvatar(UploadedFile $file): string
    {
        return $file->store('users/avatars', 'public');
    }

    private function deleteAvatar(?string $path): void
    {
        if (!$path) {
            return;
        }

        if (Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }

    private function replaceAvatar(?string $currentPath, UploadedFile $file): string
    {
        $newPath = $this->storeAvatar($file);

        if ($currentPath && $currentPath !== $newPath) {
            $this->deleteAvatar($currentPath);
        }

        return $newPath;
    }
}
