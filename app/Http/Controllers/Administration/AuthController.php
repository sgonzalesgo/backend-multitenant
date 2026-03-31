<?php

namespace App\Http\Controllers\Administration;

use App\Http\Requests\Administration\User\StoreUserRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

// local import
use App\Traits\ApiRespondTrait;
use App\Events\Presence\UserOffline;
use App\Http\Controllers\Controller;
use App\Http\Requests\Administration\LoginRequest;
use App\Repositories\Administration\AuthRepository;
use App\Repositories\Administration\UserRepository;
use App\Http\Requests\Administration\User\RegisterRequest;
use App\Http\Requests\Administration\User\SocialLoginRequest;

class AuthController extends Controller
{
    use ApiRespondTrait;

    public function __construct(
        private readonly AuthRepository $repo,
        private readonly UserRepository $user
    ) {
    }

    /**
     * POST /auth/login
     * - Valida credenciales
     * - Emite tokens
     * - Guarda access/refresh en cookies HttpOnly
     * - Devuelve solamente el payload de perfil
     *
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $data = $request->validated();

        $result = $this->repo->attemptLogin(
            $data['email'],
            $data['password']
        );

        $tokens = $result['_tokens'] ?? [];
        $payload = $result['me'] ?? $result;

        return $this->ok($payload, __('messages.logged_in'))
            ->withCookie($this->makeAccessCookie((string) ($tokens['access_token'] ?? '')))
            ->withCookie($this->makeRefreshCookie((string) ($tokens['refresh_token'] ?? '')))
            ->withCookie($this->makeXsrfCookie());
    }

    /**
     * GET /auth/me
     * - Requiere auth
     * - Devuelve perfil + tenant actual + impersonation
     */
    public function me(): JsonResponse
    {
        $payload = $this->repo->meWithImpersonation(request()->user());

        return $this->ok($payload, __('messages.profile_loaded'));
    }

    /**
     * POST /auth/switch-company
     * - Cambia tenant actual
     * - Persiste tenant_id en cookie
     * - Devuelve el mismo payload que /me
     */
    public function switchTenant(Request $request): JsonResponse
    {
        $request->validate([
            'tenant_id' => ['required'],
        ]);

        $payload = $this->repo->switchTenant(
            $request->user(),
            $request->input('tenant_id')
        );

        return $this->ok($payload, __('Tenant cambiado.'))
            ->withCookie(
                $this->makeTenantCookie((string) data_get($payload, 'current_tenant.id', ''))
            );
    }

    /**
     * POST /auth/logout
     * - Revoca token actual
     * - Borra cookies de auth
     */
    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();

        $this->repo->logout($user);

        cache()->forget("presence:online:{$user->id}");
        event(new UserOffline($user->id));

        return response()->json([
            'code' => Response::HTTP_OK,
            'message' => __('auth.logout_success'),
            'data' => null,
            'error' => null,
        ], Response::HTTP_OK)
            ->withCookie($this->forgetCookie($this->accessCookieName()))
            ->withCookie($this->forgetCookie($this->refreshCookieName()))
            ->withCookie($this->forgetCookie('XSRF-TOKEN'))
            ->withCookie($this->forgetCookie($this->tenantCookieName()));
    }

    /**
     * POST /auth/register
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $payload = $this->repo->register($request);

        $httpCode = (int) ($payload['_http_code'] ?? Response::HTTP_CREATED);
        unset($payload['_http_code']);

        return response()->json([
            'code' => $httpCode,
            'message' => __($payload['message_key'] ?? 'messages.auth.registered'),
            'data' => $payload,
            'error' => null,
        ], $httpCode);
    }

    /**
     * POST /auth/social/login
     */
    public function socialLogin(SocialLoginRequest $request): JsonResponse
    {
        $result = $this->repo->socialLogin(
            $request->input('provider'),
            $request->input('access_token'),
            $request->only(['email', 'name', 'avatar', 'locale'])
        );

        $tokens = $result['_tokens'] ?? [];
        $payload = $result['me'] ?? $result;

        return $this->ok($payload, __('messages.logged_in'))
            ->withCookie($this->makeAccessCookie((string) ($tokens['access_token'] ?? '')))
            ->withCookie($this->makeRefreshCookie((string) ($tokens['refresh_token'] ?? '')))
            ->withCookie($this->makeXsrfCookie());
    }

    //------------------- METODO NUEVOS PARA EL LOGIN CON GOOGLE Y FACEBOOK ---------------------  ---------------------
    public function socialRedirect(string $provider): RedirectResponse
    {
        $url = $this->repo->socialRedirect($provider);

        return redirect()->away($url);
    }

    public function socialCallback(Request $request, string $provider)
    {
        $result = $this->repo->socialCallback($provider);

        $tokens = $result['_tokens'] ?? [];
        $payload = $result['me'] ?? $result;

        $frontendUrl = rtrim((string) config('app.frontend_url'), '/');
        $locale = app()->getLocale();

        $redirectTo = "{$frontendUrl}/{$locale}";

        return redirect()->to($redirectTo)
            ->withCookie($this->makeAccessCookie((string) ($tokens['access_token'] ?? '')))
            ->withCookie($this->makeRefreshCookie((string) ($tokens['refresh_token'] ?? '')))
            ->withCookie($this->makeXsrfCookie());
    }
    //------------------- FIN ---------------------  ---------------------

    /**
     * POST /auth/impersonate
     * - Inicia suplantación por email
     * - Reemplaza cookies con las del usuario impersonado
     * - Devuelve solamente el payload "me"
     */
    public function impersonate(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $actor = $request->user();
        $email = (string) $request->input('email');

        $payload = $this->repo->impersonateByEmail($actor, $email);

        $tokens = $payload['_tokens'] ?? [];
        $me = $payload['me'] ?? null;

        return $this->ok($me, __('audit.auth.impersonate.start'))
            ->withCookie(
                $this->makeAccessCookie(
                    (string) ($tokens['access_token'] ?? ''),
                    (int) config('auth.tokens.impersonation_minutes', 60)
                )
            )
            ->withCookie(
                $this->makeRefreshCookie((string) ($tokens['refresh_token'] ?? ''))
            );
    }

    /**
     * POST /auth/impersonate/revert
     * - Finaliza suplantación
     * - Restaura cookies del admin original
     * - Devuelve solamente el payload "me"
     */
    public function revertImpersonation(Request $request): JsonResponse
    {
        $request->validate([
            'session_id' => ['required', 'string'],
        ]);

        $payload = $this->repo->revertImpersonationBySession(
            $request->user(),
            (string) $request->input('session_id')
        );

        $restore = $payload['_restore_tokens'] ?? [];
        $me = $payload['me'] ?? null;

        return $this->ok($me, __('audit.auth.impersonate.stop'))
            ->withCookie(
                $this->makeAccessCookie(
                    (string) ($restore['access_token'] ?? ''),
                    (int) config('auth.tokens.backup_minutes', 120)
                )
            )
            ->withCookie(
                $this->makeRefreshCookie((string) ($restore['refresh_token'] ?? ''))
            );
    }

    /**
     * POST /auth/ping
     */
    public function ping(Request $request): JsonResponse
    {
        $user = $request->user();
        $tenantId = (string) data_get($this->repo->me($user), 'current_tenant.id', '');

        if ($tenantId !== '') {
            $this->repo->markOnline($user, $tenantId);
        }

        return response()->json(['ok' => true]);
    }

    /**
     * ---------------------------------------------------------
     * Helpers de cookies
     * ---------------------------------------------------------
     */

    protected function makeAccessCookie(string $value, ?int $minutes = null)
    {
        return cookie()->make(
            name: $this->accessCookieName(),
            value: $value,
            minutes: $minutes ?? (int) config('auth.tokens.access_minutes', 15),
            path: config('session.path', '/'),
            domain: $this->cookieDomain(),
            secure: $this->cookieSecure(),
            httpOnly: true,
            raw: false,
            sameSite: $this->cookieSameSite(),
        );
    }

    protected function makeRefreshCookie(string $value, ?int $minutes = null)
    {
        return cookie()->make(
            name: $this->refreshCookieName(),
            value: $value,
            minutes: $minutes ?? ((int) config('auth.tokens.refresh_days', 30) * 24 * 60),
            path: config('session.path', '/'),
            domain: $this->cookieDomain(),
            secure: $this->cookieSecure(),
            httpOnly: true,
            raw: false,
            sameSite: $this->cookieSameSite(),
        );
    }

    protected function makeXsrfCookie(?int $minutes = null)
    {
        return cookie()->make(
            name: 'XSRF-TOKEN',
            value: csrf_token(),
            minutes: $minutes ?? (int) config('session.lifetime', 120),
            path: config('session.path', '/'),
            domain: $this->cookieDomain(),
            secure: $this->cookieSecure(),
            httpOnly: false,
            raw: false,
            sameSite: $this->cookieSameSite(),
        );
    }

    protected function makeTenantCookie(string $tenantId, ?int $minutes = null)
    {
        return cookie()->make(
            name: $this->tenantCookieName(),
            value: $tenantId,
            minutes: $minutes ?? (60 * 24 * 30),
            path: config('session.path', '/'),
            domain: $this->cookieDomain(),
            secure: $this->cookieSecure(),
            httpOnly: true,
            raw: false,
            sameSite: $this->cookieSameSite(),
        );
    }

    protected function forgetCookie(string $name)
    {
        return Cookie::forget(
            $name,
            config('session.path', '/'),
            $this->cookieDomain()
        );
    }

    protected function accessCookieName(): string
    {
        return (string) config('auth.cookie', 'access_token');
    }

    protected function refreshCookieName(): string
    {
        return (string) config('auth.refresh_cookie', 'refresh_token');
    }

    protected function tenantCookieName(): string
    {
        return (string) config('auth.tenant_cookie', 'tenant_id');
    }

    protected function cookieDomain(): ?string
    {
        $domain = config('session.domain');

        return is_string($domain) && $domain !== '' ? $domain : null;
    }

    protected function cookieSecure(): bool
    {
        return (bool) config('session.secure', app()->environment('production'));
    }

    protected function cookieSameSite(): string
    {
        return ucfirst((string) config('session.same_site', 'lax'));
    }

    public function refresh(Request $request): JsonResponse
    {
        $result = $this->repo->refresh($request);

        $tokens = $result['_tokens'] ?? [];
        $payload = $result['me'] ?? $result;

        return $this->ok($payload, __('messages.token_refreshed'))
            ->withCookie($this->makeAccessCookie((string) ($tokens['access_token'] ?? '')))
            ->withCookie($this->makeRefreshCookie((string) ($tokens['refresh_token'] ?? '')))
            ->withCookie($this->makeXsrfCookie());
    }
}
