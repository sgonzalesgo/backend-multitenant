<?php

namespace App\Repositories\Administration;

use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Spatie\Permission\PermissionRegistrar;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

// local import
use App\Events\Presence\GroupMemberOffline;
use App\Events\Presence\GroupMemberOnline;
use App\Models\Administration\ImpersonationSession;
use App\Models\Administration\Tenant;
use App\Models\Administration\User;

class AuthRepository
{
    public function __construct(
        protected ?AuditLogRepository $audit = null
    ) {
    }

    protected function audit(): AuditLogRepository
    {
        return $this->audit ??= app(AuditLogRepository::class);
    }

    /**
     * Valida credenciales y retorna perfil + tokens.
     */
    public function attemptLogin(string $email, string $password): array
    {
        try {
            /** @var User|null $user */
            $user = User::query()->where('email', $email)->first();

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

            $initialTenant = $this->resolveInitialTenantFor($user);
            $this->applyTenantContext($initialTenant);

            $tokens = $this->issuePassportTokens(
                user: $user,
                tokenName: 'web-access',
                tenantId: $initialTenant ? (string) $initialTenant->id : null
            );

            $me = $this->meWithImpersonation($user);

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
        } catch (Throwable $e) {
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
     * Debe reflejar el tenant actual efectivo de la sesión.
     */
    public function me(User $user): array
    {
        $tenant = $this->resolveCurrentTenantFor($user);
        $this->applyTenantContext($tenant);

        $user->unsetRelation('roles');
        $user->unsetRelation('permissions');

        $teamFk = config('permission.team_foreign_key', 'tenant_id');

        $roles = $tenant
            ? $user->roles()->wherePivot($teamFk, $tenant->id)->pluck('name')->values()->all()
            : $user->roles()->wherePivotNull($teamFk)->pluck('name')->values()->all();

        $tenantPermissions = $tenant
            ? $user->getAllPermissions()->pluck('name')->unique()->values()->all()
            : [];

        $this->applyTenantContext(null);
        $user->unsetRelation('roles');
        $user->unsetRelation('permissions');

        $globalPermissions = $user->getAllPermissions()->pluck('name')->unique()->values()->all();

        $this->applyTenantContext($tenant);

        $companies = $this->resolveCompaniesFor($user);

        return [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'email_verified' => (bool) $user->email_verified_at,
                'created_at' => optional($user->created_at)?->toIso8601String(),
                'updated_at' => optional($user->updated_at)?->toIso8601String(),
            ],
            'current_tenant' => $tenant,
            'roles' => $roles,
            'permissions' => $tenantPermissions,
            'global_permissions' => $globalPermissions,
            'companies' => $companies,
        ];
    }

    public function meWithImpersonation(User $user): array
    {
        return $this->withImpersonationContext($user, $this->me($user));
    }

    protected function resolveActiveImpersonationFor(User $user): ?ImpersonationSession
    {
        return ImpersonationSession::query()
            ->active()
            ->where('impersonated_id', $user->id)
            ->latest('created_at')
            ->first();
    }

    protected function withImpersonationContext(User $user, array $payload, ?ImpersonationSession $session = null): array
    {
        $session ??= $this->resolveActiveImpersonationFor($user);

        $payload['impersonation'] = [
            'active' => (bool) $session,
            'session_id' => $session?->session_id,
        ];

        return $payload;
    }

    /**
     * Tenant actual efectivo:
     * 1) Tenant::current()
     * 2) tenant_id guardado en el access token actual
     * 3) fallback al primer tenant accesible
     */
    protected function resolveCurrentTenantFor(User $user): ?Tenant
    {
        if ($current = Tenant::current()) {
            return $current;
        }

        if ($tenantFromToken = $this->resolveTenantFromAccessToken($user)) {
            return $tenantFromToken;
        }

        return $this->resolveFallbackTenantFor($user);
    }

    /**
     * Tenant inicial para login.
     */
    protected function resolveInitialTenantFor(User $user): ?Tenant
    {
        if ($tenantFromToken = $this->resolveTenantFromAccessToken($user)) {
            return $tenantFromToken;
        }

        return $this->resolveFallbackTenantFor($user);
    }

    protected function resolveFallbackTenantFor(User $user): ?Tenant
    {
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
     * Devuelve el tenant guardado en el access token actual.
     */
    protected function resolveTenantFromAccessToken(User $user): ?Tenant
    {
        $token = $user->token();

        if (!$token) {
            return null;
        }

        $tenantId = $token->tenant_id ?? null;

        if (!$tenantId) {
            return null;
        }

        return Tenant::query()->find($tenantId);
    }

    protected function applyTenantContext(?Tenant $tenant): void
    {
        $registrar = app(PermissionRegistrar::class);

        if ($tenant) {
            $tenant->makeCurrent();
            $registrar->setPermissionsTeamId($tenant->id);
        } else {
            Tenant::forgetCurrent();
            $registrar->setPermissionsTeamId(null);
        }

        $registrar->forgetCachedPermissions();
    }

    /**
     * Cambiar tenant actual de la sesión/token.
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

        if (!$canSwitch) {
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

        $this->applyTenantContext($tenant);
        $this->persistTenantIdOnCurrentAccessToken($user, (string) $tenant->id);

        return $this->meWithImpersonation($user);
    }

    /**
     * Logout.
     */
    public function logout(User $user): void
    {
        $token = $user->token();

        if (!$token) {
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

        $token->revoke();

        $conn = config('passport.connection');

        DB::connection($conn)->table('oauth_refresh_tokens')
            ->where('access_token_id', $token->id)
            ->update(['revoked' => true]);

        $tenantId = (string) ($token->tenant_id ?? Tenant::current()?->id ?? '');
        if ($tenantId !== '') {
            $this->markOffline($user, $tenantId);
        }

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
        $email = $socialUser->getEmail() ?: ($hints['email'] ?? null);
        $name = $socialUser->getName() ?: ($hints['name'] ?? 'User');
        $avatar = $socialUser->getAvatar() ?: ($hints['avatar'] ?? null);
        $locale = $hints['locale'] ?? app()->getLocale();

        $query = User::query();

        if ($provider === 'google') {
            $query->where('google_id', $providerId);
        } else {
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
     * Tenants disponibles para el usuario.
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
            ->orderBy('name')
            ->get()
            ->toArray();
    }

    /**
     * Impersonación.
     */
    public function impersonateByEmail(User $actor, string $email): array
    {
        $registrar = app(PermissionRegistrar::class);

        $actorTenant = $this->resolveTenantFromAccessToken($actor) ?? $this->resolveCurrentTenantFor($actor);
        $this->applyTenantContext($actorTenant);

        $actor->unsetRelation('roles');
        $actor->unsetRelation('permissions');

        abort_unless($this->actorCanImpersonate($actor), 403, __('errors.impersonation_forbidden'));

        /** @var User $target */
        $target = User::query()->where('email', $email)->firstOrFail();

        if ($actor->getKey() === $target->getKey()) {
            abort(422, __('errors.impersonation_same_user'));
        }

        try {
            $conn = config('passport.connection');
            $refreshDays = (int) config('auth.tokens.refresh_days', 30);
            $impMinutes = (int) config('auth.tokens.impersonation_minutes', 60);
            $backupMinutes = (int) config('auth.tokens.backup_minutes', 120);

            $actorCurrentTenantId = (string) ($actorTenant?->id ?? '');

            $teamFk = config('permission.team_foreign_key', 'tenant_id');

            $targetTenantIds = DB::table('model_has_roles')
                ->where('model_type', User::class)
                ->where('model_id', $target->getKey())
                ->whereNotNull($teamFk)
                ->pluck($teamFk)
                ->unique()
                ->filter()
                ->values();

            $effectiveTargetTenant = null;

            if ($targetTenantIds->isNotEmpty()) {
                if ($actorCurrentTenantId !== '' && $targetTenantIds->contains($actorCurrentTenantId)) {
                    $effectiveTargetTenant = Tenant::query()->find($actorCurrentTenantId);
                }

                if (!$effectiveTargetTenant) {
                    $effectiveTargetTenant = Tenant::query()
                        ->whereIn('id', $targetTenantIds->all())
                        ->orderBy('name')
                        ->first();
                }
            }

            $effectiveTargetTenantId = (string) ($effectiveTargetTenant?->id ?? '');

            $backupAccess = $actor->createToken('impersonation-backup');
            $backupAccess->token->expires_at = now()->addMinutes($backupMinutes);
            $backupAccess->token->save();

            if ($actorCurrentTenantId !== '') {
                DB::connection($conn)->table('oauth_access_tokens')
                    ->where('id', $backupAccess->token->id)
                    ->where('revoked', false)
                    ->update([
                        'tenant_id' => $actorCurrentTenantId,
                    ]);
            }

            $backupRefreshId = Str::random(64);
            DB::connection($conn)->table('oauth_refresh_tokens')->insert([
                'id' => $backupRefreshId,
                'access_token_id' => $backupAccess->token->id,
                'revoked' => false,
                'expires_at' => now()->addDays($refreshDays),
            ]);

            $impersonationSessionId = (string) Str::uuid();

            $createdImpersonationSession = ImpersonationSession::query()->create([
                'session_id' => $impersonationSessionId,
                'impersonator_id' => $actor->id,
                'impersonated_id' => $target->id,
                'actor_tenant_id' => $actorCurrentTenantId !== '' ? $actorCurrentTenantId : null,
                'backup_access_token' => $backupAccess->accessToken,
                'backup_refresh_token' => $backupRefreshId,
                'started_at' => now(),
                'expires_at' => now()->addMinutes($backupMinutes),
            ]);

            $impAccess = $target->createToken('impersonation');
            $impAccess->token->expires_at = now()->addMinutes($impMinutes);
            $impAccess->token->save();

            if ($effectiveTargetTenantId !== '') {
                DB::connection($conn)->table('oauth_access_tokens')
                    ->where('id', $impAccess->token->id)
                    ->where('revoked', false)
                    ->update([
                        'tenant_id' => $effectiveTargetTenantId,
                    ]);
            }

            $impRefreshId = Str::random(64);
            DB::connection($conn)->table('oauth_refresh_tokens')->insert([
                'id' => $impRefreshId,
                'access_token_id' => $impAccess->token->id,
                'revoked' => false,
                'expires_at' => now()->addDays($refreshDays),
            ]);

            $this->applyTenantContext($effectiveTargetTenant);

            $target->unsetRelation('roles');
            $target->unsetRelation('permissions');

            $me = $this->withImpersonationContext($target, $this->me($target), $createdImpersonationSession);

            $this->applyTenantContext($actorTenant);

            $this->audit()->log(
                actor: $actor,
                event: 'auth.impersonate.start',
                subject: $target,
                description: __('audit.auth.impersonate.start'),
                changes: ['old' => null, 'new' => null],
                tenantId: $actorCurrentTenantId !== '' ? $actorCurrentTenantId : null,
                meta: [
                    'impersonator_id' => $actor->id,
                    'impersonated_id' => $target->id,
                    'backup_access_token_id' => $backupAccess->token->id,
                    'imp_access_token_id' => $impAccess->token->id,
                    'imp_minutes' => $impMinutes,
                    'actor_tenant_id' => $actorCurrentTenantId !== '' ? $actorCurrentTenantId : null,
                    'effective_target_tenant_id' => $effectiveTargetTenantId !== '' ? $effectiveTargetTenantId : null,
                    'impersonation_session_id' => $impersonationSessionId,
                ]
            );

            return [
                'me' => $me,
                '_tokens' => [
                    'access_token' => $impAccess->accessToken,
                    'access_expires_at' => optional($impAccess->token->expires_at)?->toIso8601String(),
                    'refresh_token' => $impRefreshId,
                    'refresh_expires_at' => now()->addDays($refreshDays)->toIso8601String(),
                ],
            ];
        } catch (Throwable $e) {
            report($e);

            $this->applyTenantContext($actorTenant);

            $this->audit()->log(
                actor: $actor,
                event: 'auth.impersonate.error',
                subject: ['type' => 'Auth', 'id' => 'impersonateByEmail'],
                description: __('audit.auth.impersonate.error'),
                changes: ['old' => null, 'new' => null],
                tenantId: $actorTenant?->id,
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

    public function revertImpersonationBySession(User $currentImpersonatedUser, string $sessionId): array
    {
        /** @var ImpersonationSession $session */
        $session = ImpersonationSession::query()
            ->with(['impersonator', 'actorTenant'])
            ->active()
            ->where('session_id', $sessionId)
            ->where('impersonated_id', $currentImpersonatedUser->id)
            ->firstOrFail();

        /** @var User|null $impersonator */
        $impersonator = $session->impersonator;

        if (!$impersonator) {
            throw new HttpException(404, __('errors.user_not_found'));
        }

        try {
            $this->stopImpersonation($currentImpersonatedUser);

            $tenant = $session->actorTenant;
            $this->applyTenantContext($tenant);

            $impersonator->unsetRelation('roles');
            $impersonator->unsetRelation('permissions');

            $me = $this->me($impersonator);
            $me['impersonation'] = [
                'active' => false,
                'session_id' => null,
            ];

            $session->markEnded();

            $this->audit()->log(
                actor: $impersonator,
                event: 'auth.impersonate.stop',
                subject: $currentImpersonatedUser,
                description: __('audit.auth.impersonate.stop'),
                changes: ['old' => null, 'new' => null],
                tenantId: $tenant?->id,
                meta: [
                    'impersonation_session_id' => $session->session_id,
                    'impersonator_id' => $impersonator->id,
                    'impersonated_id' => $currentImpersonatedUser->id,
                ]
            );

            return [
                'me' => $me,
                '_restore_tokens' => [
                    'access_token' => $session->backup_access_token,
                    'refresh_token' => $session->backup_refresh_token,
                ],
            ];
        } catch (HttpException $e) {
            throw $e;
        } catch (Throwable $e) {
            report($e);

            $this->audit()->log(
                actor: $impersonator,
                event: 'auth.impersonate.revert.error',
                subject: ['type' => 'Auth', 'id' => 'revertImpersonationBySession'],
                description: __('audit.auth.impersonate.error'),
                changes: ['old' => null, 'new' => null],
                tenantId: $session->actor_tenant_id,
                meta: [
                    'impersonation_session_id' => $session->session_id,
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
     * Finaliza impersonación actual.
     */
    public function stopImpersonation(User $current): void
    {
        $token = $current->token();

        if (!$token) {
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
     * Verifica si el actor puede impersonar.
     */
    protected function actorCanImpersonate(User $actor): bool
    {
        $registrar = app(PermissionRegistrar::class);
        $prevTeamId = Tenant::current()?->id;

        $resetActor = function () use ($actor) {
            $actor->unsetRelation('roles');
            $actor->unsetRelation('permissions');
        };

        $registrar->setPermissionsTeamId(null);
        $registrar->forgetCachedPermissions();
        $resetActor();

        if ($actor->can('Impersonate users')) {
            if ($prevTeamId) {
                $registrar->setPermissionsTeamId($prevTeamId);
            } else {
                $registrar->setPermissionsTeamId(null);
            }

            $registrar->forgetCachedPermissions();
            $resetActor();

            return true;
        }

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
                if ($prevTeamId) {
                    $registrar->setPermissionsTeamId($prevTeamId);
                } else {
                    $registrar->setPermissionsTeamId(null);
                }

                $registrar->forgetCachedPermissions();
                $resetActor();

                return true;
            }
        }

        if ($prevTeamId) {
            $registrar->setPermissionsTeamId($prevTeamId);
        } else {
            $registrar->setPermissionsTeamId(null);
        }

        $registrar->forgetCachedPermissions();
        $resetActor();

        return false;
    }

    /**
     * Emite tokens Passport.
     */
    public function issuePassportTokens(User $user, string $tokenName = 'web-access', ?string $tenantId = null): array
    {
        $accessMinutes = (int) config('auth.tokens.access_minutes', 15);
        $refreshDays = (int) config('auth.tokens.refresh_days', 30);
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

    /**
     * Groups accepted por el usuario en el tenant.
     */
    protected function acceptedGroupIdsFor(User $user, string $tenantId): array
    {
        return DB::table('group_members as gm')
            ->join('groups as g', 'g.id', '=', 'gm.group_id')
            ->where('gm.user_id', $user->id)
            ->where('gm.status', 'accepted')
            ->where('g.tenant_id', $tenantId)
            ->pluck('gm.group_id')
            ->map(fn ($v) => (string) $v)
            ->all();
    }

    /**
     * Marca usuario online.
     */
    public function markOnline(User $user, string $tenantId, ?int $ttlSeconds = null): void
    {
        $ttlSeconds ??= 120;

        $key = $this->onlineKey($tenantId, (string) $user->id);
        $wasOnline = Cache::has($key);

        Cache::put($key, true, $ttlSeconds);

        if (!$wasOnline) {
            foreach ($this->acceptedGroupIdsFor($user, $tenantId) as $groupId) {
                event(new GroupMemberOnline($groupId, (string) $user->id));
            }
        }
    }

    /**
     * Marca usuario offline.
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

    public function isOnline(string $tenantId, string $userId): bool
    {
        return Cache::has($this->onlineKey($tenantId, $userId));
    }

    protected function onlineKey(string $tenantId, string $userId): string
    {
        return "presence:online:{$tenantId}:{$userId}";
    }

    protected function touchLastSeen(User $user, bool $force = false): void
    {
        $throttleSeconds = (int) config('presence.last_seen_throttle_seconds', 120);
        $lockKey = "presence:last_seen_lock:{$user->id}";

        if ($force || Cache::add($lockKey, true, $throttleSeconds)) {
            $user->forceFill(['last_seen_at' => now()])->save();
        }
    }

    /**
     * Devuelve el id del access token actual.
     */
    protected function resolveCurrentAccessTokenId(User $user): ?string
    {
        $token = $user->token();

        if (!$token) {
            return null;
        }

        return (string) $token->id;
    }

    /**
     * Persiste tenant_id en oauth_access_tokens.
     */
    protected function persistTenantIdOnCurrentAccessToken(User $user, string $tenantId): void
    {
        $tokenId = $this->resolveCurrentAccessTokenId($user);

        if (!$tokenId) {
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

    public function refresh(Request $request): array
    {
        try {
            $refreshToken = (string) $request->cookie((string) config('auth.refresh_cookie', 'refresh_token'));

            if ($refreshToken === '') {
                throw new HttpException(401, __('auth.unauthenticated'));
            }

            $conn = config('passport.connection');

            $refreshRow = DB::connection($conn)
                ->table('oauth_refresh_tokens')
                ->where('id', $refreshToken)
                ->first();

            if (!$refreshRow) {
                throw new HttpException(401, __('auth.unauthenticated'));
            }

            if ((bool) $refreshRow->revoked) {
                throw new HttpException(401, __('auth.unauthenticated'));
            }

            if (!empty($refreshRow->expires_at) && now()->greaterThan($refreshRow->expires_at)) {
                throw new HttpException(401, __('auth.unauthenticated'));
            }

            $accessRow = DB::connection($conn)
                ->table('oauth_access_tokens')
                ->where('id', $refreshRow->access_token_id)
                ->first();

            if (!$accessRow) {
                throw new HttpException(401, __('auth.unauthenticated'));
            }

            if ((bool) $accessRow->revoked) {
                throw new HttpException(401, __('auth.unauthenticated'));
            }

            $user = User::query()->find($accessRow->user_id);

            if (!$user) {
                throw new HttpException(401, __('auth.unauthenticated'));
            }

            $tenantId = !empty($accessRow->tenant_id) ? (string) $accessRow->tenant_id : null;

            // Revocación del par anterior
            DB::connection($conn)->table('oauth_refresh_tokens')
                ->where('id', $refreshToken)
                ->update(['revoked' => true]);

            DB::connection($conn)->table('oauth_access_tokens')
                ->where('id', $accessRow->id)
                ->update(['revoked' => true]);

            // Restaurar contexto tenant para el nuevo token y para el payload /me
            if ($tenantId) {
                $tenant = Tenant::query()->find($tenantId);
                $this->applyTenantContext($tenant);
            } else {
                $this->applyTenantContext(null);
            }

            $tokens = $this->issuePassportTokens(
                user: $user,
                tokenName: 'web-access',
                tenantId: $tenantId
            );

            $me = $this->meWithImpersonation($user);

            if ($tenantId) {
                $this->markOnline($user, $tenantId);
            }

            $this->audit()->log(
                actor: $user,
                event: 'auth.refresh.success',
                subject: $user,
                description: 'Refresh de token exitoso',
                changes: ['old' => null, 'new' => null],
                tenantId: $tenantId,
                meta: [
                    'old_access_token_id' => $accessRow->id,
                    'new_access_expires_at' => $tokens['access_expires_at'] ?? null,
                    'refresh_expires_at' => $tokens['refresh_expires_at'] ?? null,
                ]
            );

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
        } catch (Throwable $e) {
            report($e);

            $this->audit()->log(
                actor: null,
                event: 'auth.refresh.error',
                subject: ['type' => 'Auth', 'id' => 'refresh'],
                description: 'Error interno en refresh',
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
}
