<?php

namespace App\Http\Controllers\Administration;

use App\Http\Controllers\Controller;
use App\Http\Requests\Administration\LoginRequest;
use App\Http\Requests\Administration\User\RegisterRequest;
use App\Http\Requests\Administration\User\SocialLoginRequest;
use App\Http\Requests\Administration\User\SocialUpsertRequest;
use App\Repositories\Administration\AuthRepository;
use App\Repositories\Administration\UserRepository;
use App\Traits\ApiRespondTrait;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Administration\User;
use Carbon\Carbon;


class AuthController extends Controller
{
    use ApiRespondTrait;

    public function __construct(private readonly AuthRepository $repo,private readonly UserRepository $user)
    {
        // Aplica aquí tu middleware de multitenancy por dominio/header si es necesario antes de login
        // $this->middleware([\Spatie\Multitenancy\Http\Middleware\InitializeTenancyByDomain::class])->only(['login']);

        // Rutas protegidas:
        // $this->middleware(['bearer.from.cookie','auth:api'])->only(['me','logout','switchCompany']);
    }

    /**
     * POST /auth/login
     * - Valida credenciales
     * - Crea token API
     * - Guarda token en cookie HttpOnly/Secure (NO se envía en JSON)
     * - Responde user + roles del tenant actual
     * @throws ValidationException
     * @throws AuthenticationException
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $data = $request->validated();
        $result = $this->repo->attemptLogin($data['email'], $data['password']);

        $tokens = $result['_tokens'];
        unset($result['_tokens']); // evita filtrarlos al body

        // Duraciones (en minutos) para cookies
        $accessMinutes  = config('auth.tokens.access_minutes', 15);
        $refreshMinutes = config('auth.tokens.refresh_days', 30) * 24 * 60;

        // Cookie del ACCESS TOKEN — HttpOnly + Secure + SameSite=Lax
        $accessCookie = cookie()->make(
            name: 'access_token',
            value: $tokens['access_token'],
            minutes: $accessMinutes,
            path: '/',
            domain: null,
            secure: true,     // solo HTTPS
            httpOnly: true,   // JS no puede leerla
            raw: false,
            sameSite: 'Lax'   // mitiga CSRF en navegación
        );

        // Cookie del REFRESH TOKEN — HttpOnly + Secure + SameSite=Strict/Lax
        $refreshCookie = cookie()->make(
            name: 'refresh_token',
            value: $tokens['refresh_token'],
            minutes: $refreshMinutes,
            path: '/',
            domain: null,
            secure: true,
            httpOnly: true,
            raw: false,
            sameSite: 'Strict' // usa 'Lax' si necesitas flujo cross-site
        );

        // CSRF cookie legible por JS (para enviar en header X-XSRF-TOKEN)
        $xsrfCookie = cookie()->make(
            name: 'XSRF-TOKEN',
            value: csrf_token(),
            minutes: 120,
            path: '/',
            domain: null,
            secure: true,
            httpOnly: false,  // debe ser legible por JS
            raw: false,
            sameSite: 'Lax'
        );

        return $this->ok($result, __('messages.logged_in'))
            ->withCookie($accessCookie)
            ->withCookie($refreshCookie)
            ->withCookie($xsrfCookie);
    }

    /**
     * GET /auth/me
     * - Requiere auth
     * - Devuelve user + roles del tenant actual (según cookie X-Company-ID ya inicializada)
     */
    public function me(): JsonResponse
    {
        $payload = $this->repo->me(request()->user());

        return $this->ok($payload, __('messages.profile_loaded'));
    }

    /**
     * POST /auth/switch-tenant
     * - Autoriza el cambio (permiso global “List tenants” o rol en ese tenant).
     * - Activa el tenant y sincroniza team_id de Spatie Permission.
     * - Devuelve el mismo payload que /me ya scoping al nuevo tenant.
     * Body: { "tenant_id": <int|string> }
     */
    public function switchTenant(Request $request): JsonResponse
    {
        $request->validate([
            'tenant_id' => ['required'],
        ]);

        $payload = $this->repo->switchTenant($request->user(), $request->input('tenant_id'));

        // Opcional: persistir selección de tenant en cookie HttpOnly
        $tenantCookie = cookie(
            name: 'tenant_id',
            value: $payload['current_tenant']['id'] ?? '',
            minutes: 60 * 24 * 30,
            path: '/',
            domain: null,
            secure: true,
            httpOnly: true,
            sameSite: 'lax',
        );
        return $this->ok($payload, __('Tenant cambiado.'))->withCookie($tenantCookie);
    }

    /**
     * POST /auth/logout
     * - Revoca token actual (Passport/Sanctum)
     * - Borra cookies (token y tenant)
     */
    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();
        // Revoca access/refresh tokens y limpia contexto tenant/permissions (lo hace tu repo)
        $this->repo->logout($user);

        // Nombres de cookies según tu config
        $tokenCookieName  = config('auth.cookie', 'access_token');
        $tenantCookieName = config('tenancy.cookie', 'X-Company-ID');
        $domain           = config('session.domain'); // puede ser null en local

        // “Olvidar” cookies (path '/', domain opcional)
        $forgetTokenCookie  = Cookie::forget($tokenCookieName, '/', $domain ?: null);
        $forgetTenantCookie = Cookie::forget($tenantCookieName, '/', $domain ?: null);

        // Respuesta uniforme (200 OK) + cookies eliminadas
        return response()->json([
            'code'    => Response::HTTP_OK,
            'message' => __('auth.logout_success'),
            'data'    => null,
            'error'   => null,
        ], Response::HTTP_OK)
            ->withCookie($forgetTokenCookie)
            ->withCookie($forgetTenantCookie);
    }

    // POST /auth/register
    public function register(RegisterRequest $request): JsonResponse
    {
        $payload = $this->user->register($request->validated());

        // Opcional: setear cookie HttpOnly
        $cookieName = config('auth.cookie', 'access_token');
        $cookie     = cookie($cookieName, $payload['access_token'], 60*24*7, '/', null, true, true, false, 'lax');

        return response()
            ->json(['code'=>201,'message'=>__('messages.auth.registered'),'data'=>$payload,'error'=>null], Response::HTTP_CREATED)
            ->withCookie($cookie);
    }

    // POST /auth/social
    public function social(SocialLoginRequest $request): JsonResponse
    {
        $payload = $this->user->socialLoginOrRegister($request->input('provider'), $request->input('token'));

        $cookieName = config('auth.cookie', 'access_token');
        $cookie     = cookie($cookieName, $payload['access_token'], 60*24*7, '/', null, true, true, false, 'lax');

        return response()
            ->json(['code'=>200,'message'=>__('messages.auth.social_ok'),'data'=>$payload,'error'=>null], Response::HTTP_OK)
            ->withCookie($cookie);
    }

    public function socialUpsert(SocialUpsertRequest $request): JsonResponse
    {
        $user = $this->user->upsertFromSocialAccessToken(
            $request->input('provider'),
            $request->input('access_token'),
            $request->only(['email','name','avatar','locale'])
        );

        return response()->json([
            'code'    => 200,
            'message' => __('messages.auth.social_upsert'),
            'data'    => [
                'user' => [
                    'id'     => $user->id,
                    'name'   => $user->name,
                    'email'  => $user->email,
                    'avatar' => $user->avatar,
                    'locale' => $user->locale,
                ],
            ],
            'error'   => null,
        ]);
    }

    /**
     * Inicia suplantación por email:
     * - Setea cookies del USUARIO suplantado (access/refresh)
     * - Guarda cookies BACKUP del ADMIN (imp_access/imp_refresh)
     * - Marca bandera 'impersonating'
     */
    public function impersonate(Request $request): JsonResponse
    {
        $actor = $request->user();                // admin autenticado
        $email = (string) $request->input('email');

        $payload = $this->repo->impersonateByEmail($actor, $email);

        // === Config duraciones (minutos) ===
        $accessMinutes      = (int) config('auth.tokens.impersonation_minutes', 60);
        $refreshMinutes     = (int) config('auth.tokens.refresh_days', 30) * 24 * 60;
        $backupMinutes      = (int) config('auth.tokens.backup_minutes', 120);

        // === Nombres de cookies ===
        $accessCookieName   = config('auth.cookie', 'access_token');
        $refreshCookieName  = config('auth.refresh_cookie', 'refresh_token');
        $impAccessCookie    = 'imp_access_token';   // backup admin
        $impRefreshCookie   = 'imp_refresh_token';  // backup admin
        $flagImpersonating  = 'impersonating';      // opcional, no sensible

        // === Tokens de suplantado y backup (NO salen en el body) ===
        $tok = $payload['_tokens'] ?? [];
        $rev = $payload['_revert_tokens'] ?? [];

        // --- Cookies BACKUP del admin (HttpOnly + Secure) ---
        $bakAccessCookie = cookie()->make(
            name: $impAccessCookie,
            value: (string) ($rev['access_token'] ?? ''),
            minutes: $backupMinutes,
            path: '/',
            domain: null,
            secure: true,
            httpOnly: true,
            raw: false,
            sameSite: 'Lax'
        );
        $bakRefreshCookie = cookie()->make(
            name: $impRefreshCookie,
            value: (string) ($rev['refresh_token'] ?? ''),
            minutes: $refreshMinutes,
            path: '/',
            domain: null,
            secure: true,
            httpOnly: true,
            raw: false,
            sameSite: 'Strict'
        );

        // --- Cookies PRINCIPALES del usuario suplantado (HttpOnly + Secure) ---
        $accessCookie = cookie()->make(
            name: $accessCookieName,
            value: (string) ($tok['access_token'] ?? ''),
            minutes: $accessMinutes,
            path: '/',
            domain: null,
            secure: true,
            httpOnly: true,
            raw: false,
            sameSite: 'Lax'
        );
        $refreshCookie = cookie()->make(
            name: $refreshCookieName,
            value: (string) ($tok['refresh_token'] ?? ''),
            minutes: $refreshMinutes,
            path: '/',
            domain: null,
            secure: true,
            httpOnly: true,
            raw: false,
            sameSite: 'Strict'
        );

        // --- Bandera informativa (no HttpOnly) ---
        $flagCookie = cookie()->make(
            name: $flagImpersonating,
            value: '1',
            minutes: $accessMinutes,
            path: '/',
            domain: null,
            secure: true,
            httpOnly: false,
            raw: false,
            sameSite: 'Lax'
        );

        // Limpia los tokens del payload para no exponerlos
        unset($payload['_tokens'], $payload['_revert_tokens']);

        return response()->json([
            'code'    => 200,
            'message' => __('audit.auth.impersonate.start'),
            'data'    => [
                'user' => $payload['user'] ?? null,
                'me'   => $payload['me']   ?? null,
            ],
            'error'   => null,
        ], Response::HTTP_OK)
            ->withCookie($bakAccessCookie)
            ->withCookie($bakRefreshCookie)
            ->withCookie($accessCookie)
            ->withCookie($refreshCookie)
            ->withCookie($flagCookie);
    }

    /**
     * Finaliza suplantación:
     * - Revoca token actual del suplantado
     * - Restaura tokens del admin desde cookies BACKUP
     * - Limpia cookies de impersonación
     */
    public function revertImpersonation(Request $request): JsonResponse
    {
        // Revoca el token actual del suplantado (idempotente)
        $this->repo->stopImpersonation($request->user());

        // Nombres
        $accessCookieName   = config('auth.cookie', 'access_token');
        $refreshCookieName  = config('auth.refresh_cookie', 'refresh_token');
        $impAccessCookie    = 'imp_access_token';
        $impRefreshCookie   = 'imp_refresh_token';
        $flagImpersonating  = 'impersonating';

        // Lee backup del admin desde cookies
        $backupAccess  = (string) $request->cookie($impAccessCookie, '');
        $backupRefresh = (string) $request->cookie($impRefreshCookie, '');

        abort_if($backupAccess === '' || $backupRefresh === '', 400, __('No backup tokens to restore.'));

        // TTLs simples (como login)
        $backupMinutes  = (int) config('auth.tokens.backup_minutes', 120);
        $refreshMinutes = (int) config('auth.tokens.refresh_days', 30) * 24 * 60;

        // Reemplaza cookies principales con el backup (HttpOnly + Secure)
        $newAccessCookie = cookie()->make(
            name: $accessCookieName,
            value: $backupAccess,
            minutes: $backupMinutes,
            path: '/',
            domain: null,
            secure: true,
            httpOnly: true,
            raw: false,
            sameSite: 'Lax'
        );
        $newRefreshCookie = cookie()->make(
            name: $refreshCookieName,
            value: $backupRefresh,
            minutes: $refreshMinutes,
            path: '/',
            domain: null,
            secure: true,
            httpOnly: true,
            raw: false,
            sameSite: 'Strict'
        );

        // Limpia cookies de impersonación y bandera
        $forgetImpAT  = Cookie::forget($impAccessCookie,  '/', null);
        $forgetImpRT  = Cookie::forget($impRefreshCookie, '/', null);
        $forgetFlag   = Cookie::forget($flagImpersonating,'/', null);

        return response()->json([
            'code'    => 200,
            'message' => __('audit.auth.impersonate.stop'),
            'data'    => null,
            'error'   => null,
        ], Response::HTTP_OK)
            ->withCookie($newAccessCookie)
            ->withCookie($newRefreshCookie)
            ->withCookie($forgetImpAT)
            ->withCookie($forgetImpRT)
            ->withCookie($forgetFlag);
    }
}
