<?php

namespace App\Repositories\Administration;

use App\Models\Administration\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Laravel\Passport\PersonalAccessTokenResult;
//use Laravel\Socialite\Facades\Socialite;

class UserRepository
{
    /** Lista con filtros básicos. */
    public function list(array $filters = []): LengthAwarePaginator
    {
        $q       = Arr::get($filters, 'q');
        $sort    = Arr::get($filters, 'sort', 'name');
        $dir     = strtolower((string) Arr::get($filters, 'dir', 'asc')) === 'desc' ? 'desc' : 'asc';
        $perPage = (int) (Arr::get($filters, 'per_page', 15));

        $query = User::query();

        if ($q) {
            $query->where(function($qq) use ($q) {
                $qq->where('name','like',"%{$q}%")
                    ->orWhere('email','like',"%{$q}%");
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
            $user->name     = $data['name'];
            $user->email    = $data['email'];
            $user->password = Hash::make($data['password']);
            $user->locale   = $data['locale'] ?? app()->getLocale();
            $user->avatar   = $data['avatar'] ?? null;
            $user->is_active = true;
            $user->save();

            return $user;
        });
    }

    public function update(User $user, array $data): User
    {
        return DB::transaction(function () use ($user, $data) {
            if (array_key_exists('name', $data))      $user->name = $data['name'];
            if (array_key_exists('email', $data))     $user->email = $data['email'];
            if (array_key_exists('locale', $data))    $user->locale = $data['locale'];
            if (array_key_exists('avatar', $data))    $user->avatar = $data['avatar'];
            if (array_key_exists('is_active',$data))  $user->is_active = (bool)$data['is_active'];
            if (!empty($data['password']))            $user->password = Hash::make($data['password']);

            $user->save();
            return $user->refresh();
        });
    }

    public function delete(User $user): void
    {
        DB::transaction(function () use ($user) {
            $user->delete();
        });
    }

    // ───────────── Registro/Acceso ─────────────

    /** Registro directo y emisión de token Passport. */
    public function register(array $data): array
    {
        $user = $this->create($data);
        return $this->issueTokenPayload($user);
    }

    /**
     * Login/registro social por provider (google|facebook) y emisión de token.
     * Espera un token (id_token en Google, access_token en Facebook).
     */
//    public function socialLoginOrRegister(string $provider, string $token): array
//    {
//        $socialUser = Socialite::driver($provider)->stateless()->userFromToken($token);
//
//        // Normalizar
//        $providerId = (string) $socialUser->getId();
//        $email      = $socialUser->getEmail();
//        $name       = $socialUser->getName() ?? ($socialUser->user['name'] ?? 'User');
//
//        // Encuentra por provider_id (o por email y enlaza)
//        $user = User::query()
//            ->when($provider === 'google', fn($q) => $q->where('google_id', $providerId))
//            ->when($provider === 'facebook', fn($q) => $q->where('facebook_id', $providerId))
//            ->first();
//
//        if (! $user && $email) {
//            $user = User::query()->where('email', $email)->first();
//        }
//
//        if (! $user) {
//            // crear nuevo
//            $user = new User();
//            $user->name  = $name;
//            $user->email = $email ?? "user_{$providerId}@{$provider}.local";
//            // password random (no necesario para social), pero mantenemos política
//            $user->password = Hash::make(\Str::random(32));
//            $user->email_verified_at = $email ? now() : null;
//            $user->is_active = true;
//        }
//
//        // Enlazar provider
//        if ($provider === 'google')   $user->google_id   = $providerId;
//        if ($provider === 'facebook') $user->facebook_id = $providerId;
//
//        // Avatar si viene
//        if ($socialUser->getAvatar()) $user->avatar = $socialUser->getAvatar();
//
//        $user->save();
//
//        return $this->issueTokenPayload($user);
//    }

    /** Emite el token y devuelve payload estándar. */
    protected function issueTokenPayload(User $user): array
    {
        $tokenResult = $user->createToken('api');
        $accessToken = $tokenResult->accessToken;
        $expiresAt   = optional($tokenResult->token->expires_at)?->toIso8601String();

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
        $query = \App\Models\Administration\User::query();

        if ($provider === 'google')   { $query->where('google_id', $providerId); }
        if ($provider === 'facebook') { $query->where('facebook_id', $providerId); }

        $user = $query->first();

        if (! $user && $email) {
            $user = \App\Models\Administration\User::query()->where('email', $email)->first();
        }

        // 4) Crear o actualizar
        return \DB::transaction(function () use ($user, $provider, $providerId, $email, $name, $avatar, $locale) {
            if (! $user) {
                $user = new \App\Models\Administration\User();
                $user->email = $email ?: "user_{$provider}_{$providerId}@example.local";
                $user->password = \Illuminate\Support\Facades\Hash::make(\Str::random(40)); // placeholder
            }

            $user->name   = $name ?: $user->name;
            $user->avatar = $avatar ?: $user->avatar;
            $user->locale = $locale ?: $user->locale;
            $user->is_active = true;

            if ($provider === 'google')   { $user->google_id   = $providerId; }
            if ($provider === 'facebook') { $user->facebook_id = $providerId; }

            // Si vino email y el user no lo tenía (o era placeholder), actualízalo
            if ($email && (! $user->email || str_ends_with($user->email, '@example.local'))) {
                $user->email = $email;
            }

            // Tip: si tu modelo tiene email_verified_at y confías en el proveedor:
            // $user->email_verified_at = $email ? now() : $user->email_verified_at;

            $user->save();

            return $user->refresh();
        });
    }

}
