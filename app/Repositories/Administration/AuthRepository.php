<?php

namespace App\Repositories\Administration;

use App\Models\Administration\Tenant;
use App\Models\Administration\User;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\PermissionRegistrar;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

class AuthRepository
{
    /**
     * Valida credenciales y retorna el User (sin token).
     * Usuarios: globales.
     * @throws ValidationException
     * @throws AuthenticationException
     */
    public function attemptLogin(string $email, string $password): array
    {
        try {
            /** @var User|null $user */
            $user = User::query()->where('email', $email)->first();

            if (!$user || !Hash::check($password, $user->password)) {
                throw new AuthenticationException(__('auth.invalid_credentials'));
            }

            // === Access token (corto) ===
            $accessMinutes = (int) config('auth.tokens.access_minutes', 15);
            $access = $user->createToken('web-access'); // Passport Personal Access Token
            $access->token->expires_at = now()->addMinutes($accessMinutes);
            $access->token->save();

            // === Refresh token (largo) en tabla de Passport ===
            $refreshDays = (int) config('auth.tokens.refresh_days', 30);
            $refreshId   = Str::random(64); // cabe en VARCHAR(100) por defecto

            $conn = config('passport.connection'); // si usas otra conexión para oauth_*
            DB::connection($conn)
                ->table('oauth_refresh_tokens')
                ->insert([
                    'id'               => $refreshId,
                    'access_token_id'  => $access->token->id,
                    'revoked'          => false,
                    'expires_at'       => now()->addDays($refreshDays),
                ]);


            // Payload público + tokens (para que el Controller los ponga en cookies)
            return [
                'user' => [
                    'id'    => $user->id,
                    'name'  => $user->name,
                    'email' => $user->email,
                ],
                '_tokens' => [
                    'access_token'       => $access->accessToken,
                    'access_expires_at'  => optional($access->token->expires_at)?->toIso8601String(),
                    'refresh_token'      => $refreshId, // <-- va a cookie HttpOnly
                    'refresh_expires_at' => now()->addDays($refreshDays)->toIso8601String(),
                ],
            ];
        } catch (AuthenticationException $e) {
            throw $e; // lo formatea el Handler
        } catch (Throwable $e) {
            report($e);
            throw new HttpException(500, __('errors.server_error'));
        }
    }

    /**
     * Retorna el “perfil” del usuario autenticado, scoping por el tenant actual.
     * Tolera que el usuario no tenga roles asignados aún.
     */
    /**
     * Perfil + contexto tenant-aware.
     * - roles & permisos: del tenant ACTUAL
     * - companies: tenants a los que el usuario puede acceder (permiso global o roles por tenant)
     */
    public function me(User $user): array
    {
        /** @var Tenant|null $tenant */
        $tenant = Tenant::current();

        if ($tenant) {
            app(PermissionRegistrar::class)->setPermissionsTeamId($tenant->id);
        }

        $teamFk = config('permission.team_foreign_key', 'tenant_id');

        // Roles del tenant actual (o roles “globales” si no hay tenant y usas team_id null)
        $roles = $tenant
            ? $user->roles()->wherePivot($teamFk, $tenant->id)->pluck('name')->values()->all()
            : $user->roles()->wherePivotNull($teamFk)->pluck('name')->values()->all();

        // Permisos efectivos del tenant actual (por roles + directos con ese team_id)
        $tenantPermissions = $tenant
            ? $user->getAllPermissions()->pluck('name')->unique()->values()->all()
            : [];

        // (Opcional) Permisos globales (sin team) si te sirve reportarlos
        app(PermissionRegistrar::class)->setPermissionsTeamId(null);
        $globalPermissions = $user->getAllPermissions()->pluck('name')->unique()->values()->all();

        // Restaurar team del tenant actual para consistencia
        if ($tenant) {
            app(PermissionRegistrar::class)->setPermissionsTeamId($tenant->id);
        }

        // Tenants disponibles
        $companies = $this->resolveCompaniesFor($user);

        return [
            'user' => [
                'id'             => $user->id,
                'name'           => $user->name,
                'email'          => $user->email,
                'email_verified' => (bool) $user->email_verified_at,
                'created_at'     => optional($user->created_at)?->toIso8601String(),
                'updated_at'     => optional($user->updated_at)?->toIso8601String(),
            ],
            'current_tenant' => $tenant ? [
                'id'   => $tenant->id,
                'name' => $tenant->name ?? null,
            ] : null,

            // ACL tenant-aware
            'roles'                 => $roles,
            'permissions'           => $tenantPermissions,

            // (opcional) ACL global
            'global_permissions'    => $globalPermissions,

            // Tenants a los que puede cambiar
            'companies'             => $companies,
        ];
    }

    /**
     * POST /auth/switch-tenant
     * Regla de acceso:
     *  - permiso global 'List tenants' => puede cambiar a cualquiera
     *  - si no, solo a tenants donde tenga algún rol (model_has_roles.team_id)
     */
    public function switchTenant(User $user, int|string $tenantId): array
    {
        /** @var Tenant $tenant */
        $tenant = Tenant::query()->findOrFail($tenantId);

        $globalListTenantsPermission = 'List tenants';
        $teamFk = config('permission.team_foreign_key', 'tenant_id');

        $canSwitch = false;

        if ($user->can($globalListTenantsPermission)) {
            $canSwitch = true;
        } else {
            $hasRoleInTenant = DB::table('model_has_roles')
                ->where('model_type', User::class)
                ->where('model_id', $user->getKey())
                ->where($teamFk, $tenant->id)
                ->exists();

            $canSwitch = $hasRoleInTenant;
        }

        abort_unless($canSwitch, 403, 'No tienes acceso a este tenant.');

        // Activar tenant y sincronizar team scope
        $tenant->makeCurrent();
        app(PermissionRegistrar::class)->setPermissionsTeamId($tenant->id);

        // Reutilizar la misma vista de contexto
        return $this->me($user);
    }

    /**
     * Revoca el access token actual (y sus refresh tokens si existieran).
     * No toca cookies: eso queda en el controller.
     */
    public function logout(User $user): void
    {
        // Con Passport + HasApiTokens, esto devuelve el token del request actual
        $token = $user->token();

        if (! $token) {
            // No hay token asociado al request (p.ej., auth via cookie inválida).
            // No hacemos nada para mantener idempotencia.
            return;
        }

        // 1) Revocar access token
        // oauth_access_tokens.revoked = 1
        $token->revoke();

        // 2) Revocar refresh tokens asociados (si usás password/refresh grant)
        // oauth_refresh_tokens.revoked = 1
        DB::table('oauth_refresh_tokens')
            ->where('access_token_id', $token->id)
            ->update(['revoked' => true]);
    }

    /**
     * Tenants a los que el usuario puede acceder.
     * - Con permiso global (“List tenants”): todos
     * - Si no: solo los con rol asignado (model_has_roles.team_id)
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
            ->select('id', 'name')
            ->orderBy('name')
            ->get()
            ->toArray();
    }
}
