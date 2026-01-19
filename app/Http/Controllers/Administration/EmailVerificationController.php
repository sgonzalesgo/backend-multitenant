<?php

namespace App\Http\Controllers\Administration;

use App\Http\Controllers\Controller;
use App\Models\Administration\User;
use App\Repositories\Administration\AuthRepository;
use App\Repositories\Administration\EmailVerificationRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Symfony\Component\HttpFoundation\Response;

class EmailVerificationController extends Controller
{
    public function __construct(
        private readonly EmailVerificationRepository $repo,
        private readonly AuthRepository $authRepo,
    ) {}

    // POST /auth/verify/request-code
    public function requestCode(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        // Por privacidad, devolvemos 200 siempre, exista o no el email
        $user = User::query()->where('email', $request->input('email'))->first();
        if ($user && !$user->email_verified_at) {
            $this->repo->resendCode($user);
        }

        return response()->json([
            'code' => 200,
            'message' => __('verify.sent_ok'),
            'data' => null,
            'error' => null,
        ], Response::HTTP_OK);
    }

    // POST /auth/verify/confirm
    public function confirm(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'code'  => ['required', 'digits:6'],
        ]);

        /** @var User $user */
        $user = User::query()->where('email', $data['email'])->firstOrFail();

        // Si ya está verificado, puedes opcionalmente auto-login aquí también.
        if ($user->email_verified_at) {
            return response()->json([
                'code' => 200,
                'message' => __('verify.already'),
                'data' => null,
                'error' => null,
            ], Response::HTTP_OK);
        }

        // 1) Validar/consumir el código
        $ok = $this->repo->verifyCode($user, $data['code']);

        if (!$ok) {
            return response()->json([
                'code'    => 422,
                'message' => __('verify.invalid'),
                'data'    => null,
                'error'   => 'InvalidCode',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // 2) Marcar usuario como verificado + activo (idempotente)
        $user->forceFill([
            'email_verified_at' => now(),
            'is_active' => true,
        ])->save();

        $user = $user->refresh();

        // 3) Emitir tokens Passport + refresh (centralizado en AuthRepository)
        $tokens = $this->authRepo->issuePassportTokens($user, 'web-access');

        // 4) Cookies (misma receta que login/socialLogin)
        $domain = config('session.domain'); // null en local OK
        $secure = true; // en local quizá: app()->environment('production')

        $accessCookie = Cookie::make(
            name: 'access_token',
            value: $tokens['access_token'],
            minutes: (int) $tokens['access_minutes'],
            path: '/',
            domain: $domain,
            secure: $secure,
            httpOnly: true,
            raw: false,
            sameSite: 'Lax'
        );

        $refreshCookie = Cookie::make(
            name: 'refresh_token',
            value: $tokens['refresh_token'],
            minutes: (int) $tokens['refresh_days'] * 24 * 60,
            path: '/',
            domain: $domain,
            secure: $secure,
            httpOnly: true,
            raw: false,
            sameSite: 'Strict'
        );

        $xsrfCookie = Cookie::make(
            name: 'XSRF-TOKEN',
            value: csrf_token(),
            minutes: 120,
            path: '/',
            domain: $domain,
            secure: $secure,
            httpOnly: false,
            raw: false,
            sameSite: 'Lax'
        );

        // 5) (Recomendado) devolver perfil listo como /auth/me
        $payload = $this->authRepo->me($user);

        // 6) marcar el usuario como online
        $this->authRepo->markOnline($user);


        return response()->json([
            'code'    => 200,
            'message' => __('verify.ok'),
            'data'    => $payload,
            'error'   => null,
        ], Response::HTTP_OK)
            ->withCookie($accessCookie)
            ->withCookie($refreshCookie)
            ->withCookie($xsrfCookie);
    }
}
