<?php

namespace App\Repositories\Administration;

use App\Models\Administration\Tenant;
use App\Models\Administration\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserRepository
{
    public function __construct(
        protected ?AuditLogRepository $audit = null
    ) {}

    protected function audit(): AuditLogRepository
    {
        return $this->audit ??= app(AuditLogRepository::class);
    }

    /** Lista con filtros básicos, scoping por current tenant. */
    public function list(array $filters = []): LengthAwarePaginator
    {
        $q       = Arr::get($filters, 'q');
        $sort    = Arr::get($filters, 'sort', 'name');
        $dir     = strtolower((string) Arr::get($filters, 'dir', 'asc')) === 'desc' ? 'desc' : 'asc';
        $perPage = (int) (Arr::get($filters, 'per_page', 15));

        $query   = User::query();
        $teamFk  = config('permission.team_foreign_key', 'tenant_id');
        $tenant  = Tenant::current(); // lo setea tu middleware `tenant`
        $usersTable = (new User)->getTable(); // por si tu tabla no es 'users'

        if ($tenant) {
            $tenantId = $tenant->id;

            // (A) Usuarios con algún rol en el tenant actual
            $existsRoleInCurrent = function ($sub) use ($usersTable, $tenantId, $teamFk) {
                $sub->from('model_has_roles as mhr')
                    ->where('mhr.model_type', User::class)
                    ->whereColumn('mhr.model_id', "{$usersTable}.id")
                    ->where("mhr.{$teamFk}", $tenantId);
            };

            // (B) Usuarios sin ningún rol (en ningún tenant)
            $notExistsAnyRole = function ($sub) use ($usersTable) {
                $sub->from('model_has_roles as mhr2')
                    ->where('mhr2.model_type', User::class)
                    ->whereColumn('mhr2.model_id', "{$usersTable}.id");
            };

            $query->where(function ($qb) use ($existsRoleInCurrent, $notExistsAnyRole) {
                $qb->whereExists($existsRoleInCurrent)
                    ->orWhereNotExists($notExistsAnyRole);
            });
        } else {
            // Si por alguna razón no hay current tenant, mostramos SOLO los que no tienen roles
            $query->whereNotExists(function ($sub) use ($usersTable) {
                $sub->from('model_has_roles as mhr2')
                    ->where('mhr2.model_type', User::class)
                    ->whereColumn('mhr2.model_id', "{$usersTable}.id");
            });
        }

        if ($q) {
            $query->where(function ($qq) use ($q, $usersTable) {
                $qq->where("{$usersTable}.name", 'like', "%{$q}%")
                    ->orWhere("{$usersTable}.email", 'like', "%{$q}%");
            });
        }

        if (! in_array($sort, ['name','email','created_at','updated_at'], true)) {
            $sort = 'name';
        }

        return $query->orderBy($sort, $dir)->paginate($perPage);
    }

    public function all(): Collection
    {
       return User::query()->orderBy('name')->get();
    }

    public function findOrFail(string $id): User
    {
        return User::query()->findOrFail($id);
    }

    public function create(array $data): User
    {
        return DB::transaction(function () use ($data) {
            $user = new User();
            $user->name      = $data['name'];
            $user->email     = $data['email'];
            $user->password  = Hash::make($data['password']); // nunca loguear password
            $user->avatar    = $data['avatar'] ?? null;
            $user->is_active = true;
            $user->save();

            // Audit
            $this->audit()->log(
                actor: auth()->user(),
                event: 'Usuario creado',
                subject: $user,
                description: __('audit.users.created'),
                changes: ['old' => null, 'new' => Arr::only($user->toArray(), ['id','name','email','avatar','locale','is_active'])],
                tenantId: Tenant::current()?->id
            );

            return $user;
        });
    }

    public function update(User $user, array $data): User
    {
        return DB::transaction(function () use ($user, $data) {
            $old = Arr::only($user->getOriginal(), ['name','email','locale','avatar','is_active']);

            if (array_key_exists('name', $data))      $user->name = $data['name'];
            if (array_key_exists('email', $data))     $user->email = $data['email'];
            if (array_key_exists('locale', $data))    $user->locale = $data['locale'];
            if (array_key_exists('avatar', $data))    $user->avatar = $data['avatar'];
            if (array_key_exists('is_active',$data))  $user->is_active = (bool)$data['is_active'];
            if (!empty($data['password']))            $user->password = Hash::make($data['password']); // no loguear

            $user->save();
            $fresh = $user->refresh();

            // Audit
            $this->audit()->log(
                actor: auth()->user(),
                event: 'Usuario actualizado',
                subject: $fresh,
                description: __('audit.users.updated'),
                changes: ['old' => $old, 'new' => Arr::only($fresh->toArray(), ['name','email','locale','avatar','is_active'])],
                tenantId: Tenant::current()?->id
            );

            return $fresh;
        });
    }

    public function delete(User $user): void
    {
        DB::transaction(function () use ($user) {
            $snapshot = Arr::only($user->toArray(), ['id','name','email','locale','avatar','is_active']);

            $user->delete();

            // Audit
            $this->audit()->log(
                actor: auth()->user(),
                event: 'Usuario eliminado',
                subject: ['type' => User::class, 'id' => $snapshot['id']],
                description: __('audit.users.deleted'),
                changes: ['old' => $snapshot, 'new' => null],
                tenantId: Tenant::current()?->id
            );
        });
    }

    // ───────────── Registro/Acceso ─────────────

    /** Registro directo y emisión de token Passport. */
    public function register(array $data): array
    {
        $user = $this->create($data); // create ya audita

        // al crear:
        $user->is_active = false; // hasta verificar
        $user->email_verified_at = null;
        $user->save();

        // emitir código de verificacion por correo
        app(EmailVerificationRepository::class)
            ->issueCode($user, 'verify_email', request()->ip(), request()->userAgent());


        // Audit (registro + emisión de token, sin token en logs)
        $this->audit()->log(
            actor: auth()->user(), // podría ser null si es público
            event: 'users.register',
            subject: $user,
            description: __('audit.users.register'),
            changes: ['old' => null, 'new' => ['id' => $user->id, 'email' => $user->email]],
            tenantId: Tenant::current()?->id
        );

        return $this->issueTokenPayload($user); // no audito aquí para no duplicar
    }

    /** Emite el token y devuelve payload estándar. */
    protected function issueTokenPayload(User $user): array
    {
        $tokenResult = $user->createToken('api');
        $accessToken = $tokenResult->accessToken;
        $expiresAt   = optional($tokenResult->token->expires_at)?->toIso8601String();

        // (Opcional) Si quisieras auditar aquí, evita guardar el token.
        // $this->audit()->log(...);

        return [
            'user'         => [
                'id'    => $user->id,
                'name'  => $user->name,
                'email' => $user->email,
                'avatar'=> $user->avatar,
                'locale'=> $user->locale,
            ],
            'access_token' => $accessToken,
            'expires_at'   => $expiresAt,
        ];
    }

    public function upsertFromSocialAccessToken(string $provider, string $accessToken, array $hints = []): User
    {
        // 1) Obtener perfil desde el proveedor con Socialite (stateless)
        $socialUser = \Laravel\Socialite\Facades\Socialite::driver($provider)
            ->stateless()
            ->userFromToken($accessToken);

        // 2) Normalizar datos
        $providerId = (string) $socialUser->getId();
        $email      = $socialUser->getEmail() ?: ($hints['email'] ?? null);
        $name       = $socialUser->getName()  ?: ($hints['name']  ?? 'User');
        $avatar     = $socialUser->getAvatar() ?: ($hints['avatar'] ?? null);
        $locale     = $hints['locale'] ?? app()->getLocale();

        // 3) Buscar usuario por provider_id; si no, por email (y enlazar)
        $query = User::query();

        if ($provider === 'google')   { $query->where('google_id', $providerId); }
        if ($provider === 'facebook') { $query->where('facebook_id', $providerId); }

        $user = $query->first();

        if (! $user && $email) {
            $user = User::query()->where('email', $email)->first();
        }

        $created = ! $user;

        // 4) Crear o actualizar
        return DB::transaction(function () use ($user, $provider, $providerId, $email, $name, $avatar, $locale, $created) {
            if (! $user) {
                $user = new User();
                $user->email = $email ?: "user_{$provider}_{$providerId}@example.local";
                $user->password = Hash::make(\Str::random(40)); // placeholder
            }

            $user->name     = $name ?: $user->name;
            $user->avatar   = $avatar ?: $user->avatar;
            $user->locale   = $locale ?: $user->locale;
            $user->is_active = true;

            if ($provider === 'google')   { $user->google_id   = $providerId; }
            if ($provider === 'facebook') { $user->facebook_id = $providerId; }

            // Si vino email y el user no lo tenía (o era placeholder), actualízalo
            if ($email && (! $user->email || str_ends_with($user->email, '@example.local'))) {
                $user->email = $email;
            }

            $user->save();
            $fresh = $user->refresh();

            // Audit (no guardamos token ni accessToken de proveedor)
            $this->audit()->log(
                actor: auth()->user(), // puede ser null si es flujo público
                event: 'users.social.upsert',
                subject: $fresh,
                description: $created ? __('audit.users.social.created') : __('audit.users.social.updated'),
                changes: [
                    'old' => null,
                    'new' => Arr::only($fresh->toArray(), ['id','email','name','locale','is_active'])
                ],
                tenantId: Tenant::current()?->id,
                meta: [
                    'provider' => $provider,
                    'linked'   => true,
                ]
            );

            return $fresh;
        });
    }
}
