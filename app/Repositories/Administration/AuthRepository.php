<?php

namespace App\Repositories\Administration;

// global import
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\PermissionRegistrar;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;
use Exception;
use Illuminate\Support\Facades\Cache;

// local import
use App\Events\Presence\UserOffline;
use App\Events\Presence\UserOnline;
use App\Models\Administration\Tenant;
use App\Models\Administration\User;
use App\Events\Presence\GroupMemberOnline;
use App\Events\Presence\GroupMemberOffline;


/** Si tu AuditLogRepository está en este mismo namespace, no necesitas el use.
 *  Des-comenta el siguiente use si lo tienes en otro lado.
 */
// use App\Repositories\Administration\AuditLogRepository;

class AuthRepository
{
    public function __construct(
        protected ?AuditLogRepository $audit = null
    )
    {
    }

    /** Helper para obtener el repo de auditoría aunque no esté inyectado */
    protected function audit(): AuditLogRepository
    {
        return $this->audit ??= app(AuditLogRepository::class);
    }

    /**
     * Valida credenciales y retorna el User (sin token).
     * Usuarios: globales.
     */
    public function attemptLogin(string $email, string $password): array
    {
        try {
            /** @var User|null $user */
            $user = User::query()->where('email', $email)->first();

            // Credenciales inválidas
            if (!$user || !Hash::check($password, (string) $user->password)) {
                $this->audit()->log(
                    actor: $user,
                    event: 'auth.login.failed',
                    subject: $user ?: null,
                    description: 'Intento de login fallido',
                    changes: ['old' => null, 'new' => null],
                    tenantId: Tenant::current()?->id,
                    meta: ['email' => $email, 'reason' => 'invalid_credentials']
                );

                throw new HttpException(401, __('auth.invalid_credentials'));
            }

            // Cuenta inactiva
            if ($user->is_active === false) {
                $this->audit()->log(
                    actor: $user,
                    event: 'auth.login.blocked.inactive',
                    subject: $user,
                    description: 'Login bloqueado: cuenta inactiva',
                    changes: ['old' => null, 'new' => null],
                    tenantId: Tenant::current()?->id
                );

                throw new HttpException(403, __('auth.account_inactive'));
            }

            // Correo no verificado
            if (is_null($user->email_verified_at)) {
                $this->audit()->log(
                    actor: $user,
                    event: 'auth.login.blocked.unverified',
                    subject: $user,
                    description: 'Login bloqueado: email no verificado',
                    changes: ['old' => null, 'new' => null],
                    tenantId: Tenant::current()?->id
                );

                throw new HttpException(403, __('auth.email_not_verified'));
            }

            // 1) Resolver tenant inicial del usuario
            $initialTenant = $this->resolveInitialTenantFor($user);

            if ($initialTenant) {
                $initialTenant->makeCurrent();

                $registrar = app(PermissionRegistrar::class);
                $registrar->setPermissionsTeamId($initialTenant->id);
                $registrar->forgetCachedPermissions();
            }

            // 2) Emitir tokens
            $tokens = $this->issuePassportTokens(
                user: $user,
                tokenName: 'web-access',
                tenantId: $initialTenant?->id
            );

            // 3) Construir payload /me ya con el token y tenant correctos
            $me = $this->me($user);


            // 4) Marcar online
            $tenantId = (string) ($initialTenant?->id ?? '');
            if ($tenantId !== '') {
                $this->markOnline($user, $tenantId);
            }

            return [
                'me' => $me,
                '_tokens' => [
                    'access_token' => $tokens['access_token'],
                    'access_expires_at' => $tokens['access_expires_at'],
                    'refresh_token' => $tokens['refresh_token'],
                    'refresh_expires_at' => $tokens['refresh_expires_at'],
                ],
            ];
        } catch (HttpException $e) {
            throw $e;
        } catch (\Throwable $e) {
            report($e);

            $this->audit()->log(
                actor: null,
                event: 'auth.login.error',
                subject: ['type' => 'Auth', 'id' => 'attemptLogin'],
                description: 'Error interno en login',
                changes: ['old' => null, 'new' => null],
                tenantId: Tenant::current()?->id,
                meta: [
                    'exception' => class_basename($e),
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ]
            );

            throw new HttpException(500, __('errors.server_error'));
        }
    }

    /**
     * Perfil + contexto tenant-aware.
     * - Si hay Tenant::current(), se respeta.
     * - Si no, se infiere uno en base a roles del usuario (team_id).
     */
    public function me(User $user): array
    {
        $tenant = $this->resolveInitialTenantFor($user);
        $registrar = app(PermissionRegistrar::class);

        if ($tenant) {
            $tenant->makeCurrent();
            $registrar->setPermissionsTeamId($tenant->id);
            $registrar->forgetCachedPermissions();
        } else {
            $registrar->setPermissionsTeamId(null);
            $registrar->forgetCachedPermissions();
        }

        // Evita relaciones “quedadas”
        $user->unsetRelation('roles');
        $user->unsetRelation('permissions');

        $teamFk = config('permission.team_foreign_key', 'tenant_id');

        $roles = $tenant
            ? $user->roles()->wherePivot($teamFk, $tenant->id)->pluck('name')->values()->all()
            : $user->roles()->wherePivotNull($teamFk)->pluck('name')->values()->all();

        $tenantPermissions = $tenant
            ? $user->getAllPermissions()->pluck('name')->unique()->values()->all()
            : [];

        // Global
        $registrar->setPermissionsTeamId(null);
        $registrar->forgetCachedPermissions();
        $user->unsetRelation('roles');
        $user->unsetRelation('permissions');

        $globalPermissions = $user->getAllPermissions()->pluck('name')->unique()->values()->all();

        // Restaurar team
        if ($tenant) {
            $registrar->setPermissionsTeamId($tenant->id);
            $registrar->forgetCachedPermissions();
        }

        $companies = $this->resolveCompaniesFor($user);

        return [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'email_verified' => (bool)$user->email_verified_at,
                'created_at' => optional($user->created_at)?->toIso8601String(),
                'updated_at' => optional($user->updated_at)?->toIso8601String(),
            ],
            'current_tenant' => $tenant ?? null,

            'roles' => $roles,
            'permissions' => $tenantPermissions,
            'global_permissions' => $globalPermissions,
            'companies' => $companies,
        ];
    }

    /**
     * Deduce el tenant “actual” cuando no hay uno activo.
     */
    protected function resolveInitialTenantFor(User $user): ?Tenant
    {
        // 1) Si ya hay un tenant actual resuelto por Spatie, usarlo
        if ($current = Tenant::current()) {
            return $current;
        }

        // 2) Si el token actual ya tiene tenant_id, usarlo
        if ($tenantFromToken = $this->resolveTenantFromAccessToken($user)) {
            return $tenantFromToken;
        }

        // 3) Fallback: primer tenant al que el usuario tiene acceso
        $teamFk = config('permission.team_foreign_key', 'tenant_id');

        $tenantIds = DB::table('model_has_roles')
            ->where('model_type', User::class)
            ->where('model_id', $user->getKey())
            ->whereNotNull($teamFk)
            ->pluck($teamFk)
            ->unique()
            ->filter()
            ->values();

        if ($tenantIds->isEmpty()) {
            return null;
        }

        return Tenant::query()
            ->whereIn('id', $tenantIds->all())
            ->orderBy('name')
            ->first();
    }

    /**
     * Devuelve el tenant actual guardado en el access token del usuario.
     */
    protected function resolveTenantFromAccessToken(User $user): ?Tenant
    {
        $token = $user->token();

        if (! $token) {
            return null;
        }

        $tenantId = $token->tenant_id ?? null;

        if (! $tenantId) {
            return null;
        }

        return Tenant::query()->find($tenantId);
    }

    /**
     * POST /auth/switch-tenant
     */
    public function switchTenant(User $user, int|string $tenantId): array
    {
        /** @var Tenant $tenant */
        $tenant = Tenant::query()->findOrFail($tenantId);

        $globalListTenantsPermission = 'List tenants';
        $teamFk = config('permission.team_foreign_key', 'tenant_id');

        $canSwitch = $user->can($globalListTenantsPermission)
            || DB::table('model_has_roles')
                ->where('model_type', User::class)
                ->where('model_id', $user->getKey())
                ->where($teamFk, $tenant->id)
                ->exists();

        if (! $canSwitch) {
            $this->audit()->log(
                actor: $user,
                event: 'Cambio de Tenant denegado',
                subject: $tenant,
                description: 'Cambio de tenant denegado',
                changes: ['old' => null, 'new' => null],
                tenantId: Tenant::current()?->id,
                meta: ['target_tenant_id' => $tenant->id]
            );

            abort(403, 'No tienes acceso a este tenant.');
        }

        // Activar tenant actual en esta request
        $tenant->makeCurrent();

        $registrar = app(PermissionRegistrar::class);
        $registrar->setPermissionsTeamId($tenant->id);
        $registrar->forgetCachedPermissions();

        // ✅ Persistir tenant en el token actual
        $this->persistTenantIdOnCurrentAccessToken($user, (string) $tenant->id);

        return $this->me($user);
    }

    /**
     * Revoca el access token actual (y sus refresh tokens si existieran).
     */
    public function logout(User $user): void
    {
        $token = $user->token();

        if (!$token) {
            // Log “idempotente” sin token asociado
            $this->audit()->log(
                actor: $user,
                event: 'Error en el cierre de Session',
                subject: $user,
                description: 'Logout sin token asociado al request',
                changes: ['old' => null, 'new' => null],
                tenantId: Tenant::current()?->id
            );
            $tenantId = (string) (Tenant::current()?->id ?? '');
            if ($tenantId !== '') {
                $this->markOffline($user, $tenantId);
            }

            return;
        }

        // 1) Revocar access token
        $token->revoke();

        // 2) Revocar refresh tokens asociados
        $conn = config('passport.connection');

        DB::connection($conn)->table('oauth_refresh_tokens')
            ->where('access_token_id', $token->id)
            ->update(['revoked' => true]);

        // 3)marcar el usuario como offline
        $tenantId = (string) (Tenant::current()?->id ?? '');
        if ($tenantId !== '') {
            $this->markOffline($user, $tenantId);
        }

        // Log de logout
        $this->audit()->log(
            actor: $user,
            event: 'Cierre de sesión exitoso',
            subject: $user,
            description: 'Cierre de sesión',
            changes: ['old' => null, 'new' => null],
            tenantId: Tenant::current()?->id,
            meta: ['access_token_id' => $token->id]
        );
    }

    public function upsertFromSocialAccessToken(string $provider, string $accessToken, array $hints = []): User
    {
        if (!in_array($provider, ['google', 'facebook'], true)) {
            throw new HttpException(422, 'Proveedor social no soportado.');
        }

        $socialUser = \Laravel\Socialite\Facades\Socialite::driver($provider)
            ->stateless()
            ->userFromToken($accessToken);

        $providerId = (string) $socialUser->getId();
        $email  = $socialUser->getEmail() ?: ($hints['email'] ?? null);
        $name   = $socialUser->getName() ?: ($hints['name'] ?? 'User');
        $avatar = $socialUser->getAvatar() ?: ($hints['avatar'] ?? null);
        $locale = $hints['locale'] ?? app()->getLocale();

        $query = User::query();

        if ($provider === 'google') {
            $query->where('google_id', $providerId);
        } else { // facebook
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
                $user->password = Hash::make(Str::random(40));
            }

            $user->name = $name ?: $user->name;
            $user->avatar = $avatar ?: $user->avatar;
            $user->locale = $locale ?: $user->locale;
            $user->is_active = true;

            if ($provider === 'google') {
                $user->google_id = $providerId;
            } else {
                $user->facebook_id = $providerId;
            }

            if ($email && (!$user->email || str_ends_with($user->email, '@example.local'))) {
                $user->email = $email;
            }

            $user->save();
            $fresh = $user->refresh();

            $this->audit()->log(
                actor: auth()->user(),
                event: 'users.social.upsert',
                subject: $fresh,
                description: $created ? __('audit.users.social.created') : __('audit.users.social.updated'),
                changes: [
                    'old' => null,
                    'new' => Arr::only($fresh->toArray(), ['id', 'email', 'name', 'locale', 'is_active'])
                ],
                tenantId: Tenant::current()?->id,
                meta: ['provider' => $provider, 'linked' => true]
            );

            return $fresh;
        });
    }

    /**
     * Tenants a los que el usuario puede acceder.
     */
    protected function resolveCompaniesFor(User $user): array
    {
        $globalListTenantsPermission = 'List tenants';

        if ($user->can($globalListTenantsPermission)) {
            return Tenant::query()
                ->select('id', 'name')
                ->orderBy('name')
                ->get()
                ->toArray();
        }

        $teamFk = config('permission.team_foreign_key', 'tenant_id');

        $tenantIds = DB::table('model_has_roles')
            ->where('model_type', User::class)
            ->where('model_id', $user->getKey())
            ->whereNotNull($teamFk)
            ->pluck($teamFk)
            ->unique()
            ->filter()
            ->values();

        if ($tenantIds->isEmpty()) {
            return [];
        }

        return Tenant::query()
            ->whereIn('id', $tenantIds->all())
//            ->select('id', 'name')
            ->orderBy('name')
            ->get()
            ->toArray();
    }

    /**
     * Impersonar por email: emite tokens para el usuario objetivo y
     * tokens de respaldo para el admin (para poder revertir).
     *
     * @throws HttpException
     */
    public function impersonateByEmail(User $actor, string $email): array
    {
        // Seguridad: permiso global o en cualquier tenant
        abort_unless($this->actorCanImpersonate($actor), 403, __('errors.impersonation_forbidden'));

        /** @var User $target */
        $target = User::query()->where('email', $email)->firstOrFail();

        // Evitar suplantarse a sí mismo (opcional, pero recomendable)
        if ($actor->getKey() === $target->getKey()) {
            abort(422, __('errors.impersonation_same_user'));
        }

        try {
            // Config
            $conn = config('passport.connection'); // conexión de oauth_*
            $refreshDays = (int)config('auth.tokens.refresh_days', 30);
            $impMinutes = (int)config('auth.tokens.impersonation_minutes', 60);
            $backupMinutes = (int)config('auth.tokens.backup_minutes', 120);

            // 1) Emitir TOKEN BACKUP para el admin (para revertir)
            $backupAccess = $actor->createToken('impersonation-backup');
            $backupAccess->token->expires_at = now()->addMinutes($backupMinutes);
            $backupAccess->token->save();

            $backupRefreshId = Str::random(64);
            DB::connection($conn)->table('oauth_refresh_tokens')->insert([
                'id' => $backupRefreshId,
                'access_token_id' => $backupAccess->token->id,
                'revoked' => false,
                'expires_at' => now()->addDays($refreshDays),
            ]);

            // 2) Emitir TOKEN del usuario suplantado
            $impAccess = $target->createToken('impersonation');
            $impAccess->token->expires_at = now()->addMinutes($impMinutes);
            $impAccess->token->save();

            $impRefreshId = Str::random(64);
            DB::connection($conn)->table('oauth_refresh_tokens')->insert([
                'id' => $impRefreshId,
                'access_token_id' => $impAccess->token->id,
                'revoked' => false,
                'expires_at' => now()->addDays($refreshDays),
            ]);

            // 3) Auditoría (no guardamos tokens)
            $this->audit()->log(
                actor: $actor,
                event: 'auth.impersonate.start',
                subject: $target,
                description: __('audit.auth.impersonate.start'),
                changes: ['old' => null, 'new' => null],
                tenantId: Tenant::current()?->id,
                meta: [
                    'impersonator_id' => $actor->id,
                    'impersonated_id' => $target->id,
                    'backup_access_token_id' => $backupAccess->token->id,
                    'imp_access_token_id' => $impAccess->token->id,
                    'imp_minutes' => $impMinutes,
                ]
            );

            // 4) Devolver también el “me” del usuario suplantado
            $me = $this->me($target);

            return [
                'user' => [
                    'id' => $target->id,
                    'name' => $target->name,
                    'email' => $target->email,
                ],
                'me' => $me,

                // Tokens del usuario suplantado (para setear en cookies principales)
                '_tokens' => [
                    'access_token' => $impAccess->accessToken,
                    'access_expires_at' => optional($impAccess->token->expires_at)?->toIso8601String(),
                    'refresh_token' => $impRefreshId,
                    'refresh_expires_at' => now()->addDays($refreshDays)->toIso8601String(),
                ],

                // Tokens BACKUP del admin (guárdalos aparte para “desimpersonar”)
                '_revert_tokens' => [
                    'access_token' => $backupAccess->accessToken,
                    'access_expires_at' => optional($backupAccess->token->expires_at)?->toIso8601String(),
                    'refresh_token' => $backupRefreshId,
                    'refresh_expires_at' => now()->addDays($refreshDays)->toIso8601String(),
                ],
            ];
        } catch (Throwable $e) {
            report($e);
            $this->audit()->log(
                actor: $actor,
                event: 'auth.impersonate.error',
                subject: ['type' => 'Auth', 'id' => 'impersonateByEmail'],
                description: __('audit.auth.impersonate.error'),
                changes: ['old' => null, 'new' => null],
                tenantId: Tenant::current()?->id,
                meta: ['exception' => class_basename($e)]
            );
            throw new HttpException(500, __('errors.server_error'));
        }
    }

    /**
     * Finaliza una sesión de suplantación revocando el token actual.
     * Úsalo antes de restaurar los cookies con los _revert_tokens del admin.
     */
    public function stopImpersonation(User $current): void
    {
        $token = $current->token();
        if (!$token) {
            // idempotente
            $this->audit()->log(
                actor: $current,
                event: 'auth.impersonate.stop',
                subject: $current,
                description: __('audit.auth.impersonate.stop_no_token'),
                changes: ['old' => null, 'new' => null],
                tenantId: Tenant::current()?->id
            );
            return;
        }

        // revocar access + refresh
        $token->revoke();
        $conn = config('passport.connection');

        DB::connection($conn)->table('oauth_refresh_tokens')
            ->where('access_token_id', $token->id)
            ->update(['revoked' => true]);

        $this->audit()->log(
            actor: $current,
            event: 'auth.impersonate.stop',
            subject: $current,
            description: __('audit.auth.impersonate.stop'),
            changes: ['old' => null, 'new' => null],
            tenantId: Tenant::current()?->id,
            meta: ['revoked_access_token_id' => $token->id]
        );
    }

    /**
     * Verifica si el actor tiene "Impersonate users" globalmente
     * o en cualquier tenant donde tenga roles.
     */
    protected function actorCanImpersonate(User $actor): bool
    {
        $registrar = app(PermissionRegistrar::class);

        // Guardar y luego restaurar
        $prevTeamId = Tenant::current()?->id;

        $resetActor = function () use ($actor) {
            $actor->unsetRelation('roles');
            $actor->unsetRelation('permissions');
        };

        // 1) Global (team_id = null)
        $registrar->setPermissionsTeamId(null);
        $registrar->forgetCachedPermissions();
        $resetActor();
        if ($actor->can('Impersonate users')) {
            if ($prevTeamId) {
                $registrar->setPermissionsTeamId($prevTeamId);
            }
            return true;
        }

        // 2) En cualquier tenant donde tenga rol
        $teamFk = config('permission.team_foreign_key', 'tenant_id');
        $tenantIds = DB::table('model_has_roles')
            ->where('model_type', User::class)
            ->where('model_id', $actor->getKey())
            ->whereNotNull($teamFk)
            ->pluck($teamFk)
            ->unique()
            ->filter()
            ->values()
            ->all();

        foreach ($tenantIds as $tid) {
            $registrar->setPermissionsTeamId($tid);
            $registrar->forgetCachedPermissions();
            $resetActor();
            if ($actor->can('Impersonate users')) {
                // restaurar y permitir
                if ($prevTeamId) {
                    $registrar->setPermissionsTeamId($prevTeamId);
                } else {
                    $registrar->setPermissionsTeamId(null);
                }
                return true;
            }
        }

        // restaurar
        if ($prevTeamId) {
            $registrar->setPermissionsTeamId($prevTeamId);
        } else {
            $registrar->setPermissionsTeamId(null);
        }

        return false;
    }

    // para emitir tokens Passport (reutilizable)
    public function issuePassportTokens(User $user, string $tokenName = 'web-access', ?string $tenantId = null): array
    {
        $accessMinutes = (int) config('auth.tokens.access_minutes', 15);
        $refreshDays   = (int) config('auth.tokens.refresh_days', 30);
        $conn = config('passport.connection');

        $access = $user->createToken($tokenName);

        $access->token->expires_at = now()->addMinutes($accessMinutes);
        $access->token->save();

        if ($tenantId) {
            DB::connection($conn)->table('oauth_access_tokens')
                ->where('id', $access->token->id)
                ->where('revoked', false)
                ->update([
                    'tenant_id' => $tenantId,
                ]);
        }

        $refreshId = Str::random(64);

        DB::connection($conn)->table('oauth_refresh_tokens')->insert([
            'id' => $refreshId,
            'access_token_id' => $access->token->id,
            'revoked' => false,
            'expires_at' => now()->addDays($refreshDays),
        ]);

        return [
            'access_token' => $access->accessToken,
            'access_expires_at' => optional($access->token->expires_at)?->toIso8601String(),
            'refresh_token' => $refreshId,
            'refresh_expires_at' => now()->addDays($refreshDays)->toIso8601String(),
            'access_minutes' => $accessMinutes,
            'refresh_days' => $refreshDays,
        ];
    }

    // accepted groups for user
    protected function acceptedGroupIdsFor(User $user, string $tenantId): array
    {
        return DB::table('group_members as gm')
            ->join('groups as g', 'g.id', '=', 'gm.group_id')
            ->where('gm.user_id', $user->id)
            ->where('gm.status', 'accepted')
            ->where('g.tenant_id', $tenantId)
            ->pluck('gm.group_id')
            ->map(fn($v) => (string) $v)
            ->all();
    }

    /**
     * Marca al usuario como online en cache con TTL.
     * Usa Redis (CACHE_DRIVER=redis).
     */
    public function markOnline(User $user, string $tenantId, ?int $ttlSeconds = null): void
    {
        $ttlSeconds ??= 120;

        $key = $this->onlineKey($tenantId, (string) $user->id);
        $wasOnline = Cache::has($key);

        Cache::put($key, true, $ttlSeconds);

        if (! $wasOnline) {
            foreach ($this->acceptedGroupIdsFor($user, $tenantId) as $groupId) {
                event(new GroupMemberOnline($groupId, (string) $user->id));
            }
        }
    }

    /**
     * Marca al usuario como offline (borra flag).
     */
    public function markOffline(User $user, string $tenantId): void
    {
        $key = $this->onlineKey($tenantId, (string) $user->id);
        $wasOnline = Cache::has($key);

        Cache::forget($key);

        if ($wasOnline) {
            foreach ($this->acceptedGroupIdsFor($user, $tenantId) as $groupId) {
                event(new GroupMemberOffline($groupId, (string) $user->id));
            }
        }
    }

    /**
     * Devuelve true si está online (según TTL).
     */
    public function isOnline(string $tenantId, string $userId): bool
    {
        return Cache::has($this->onlineKey($tenantId, $userId));
    }

    // MARK: Online users
    protected function onlineKey(string $tenantId, string $userId): string
    {
        return "presence:online:{$tenantId}:{$userId}";
    }

    /**
     * (Opcional) Guardar last_seen_at con throttle (cada N segundos).
     * Requiere columna users.last_seen_at si lo quieres.
     */
    protected function touchLastSeen(User $user, bool $force = false): void
    {
        $throttleSeconds = (int) config('presence.last_seen_throttle_seconds', 120);

        $lockKey = "presence:last_seen_lock:{$user->id}";

        if ($force || Cache::add($lockKey, true, $throttleSeconds)) {
            // Si NO tienes last_seen_at, comenta esta línea
            $user->forceFill(['last_seen_at' => now()])->save();
        }
    }

    /**
     * Devuelve el id del access token actual del usuario autenticado.
     */
    protected function resolveCurrentAccessTokenId(User $user): ?string
    {
        $token = $user->token();

        if (! $token) {
            return null;
        }

        return (string) $token->id;
    }

    /**
     * Actualiza el tenant_id del access token actual directamente en oauth_access_tokens.
     */
    protected function persistTenantIdOnCurrentAccessToken(User $user, string $tenantId): void
    {
        $tokenId = $this->resolveCurrentAccessTokenId($user);

        if (! $tokenId) {
            return;
        }

        $conn = config('passport.connection');

        DB::connection($conn)->table('oauth_access_tokens')
            ->where('id', $tokenId)
            ->where('revoked', false)
            ->update([
                'tenant_id' => $tenantId,
            ]);
    }

}
