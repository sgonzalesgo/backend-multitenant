<?php

namespace App\Http\Controllers\Administration;

use App\Http\Controllers\Controller;
use App\Http\Requests\Administration\User\ForgotPasswordConfirmRequest;
use App\Http\Requests\Administration\User\ForgotPasswordRequestCodeRequest;
use App\Repositories\Administration\AuthRepository;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class ForgotPasswordController extends Controller
{
    public function __construct(
        private readonly AuthRepository $repo,
    ) {}

    /**
     * POST /auth/password/forgot/request-code
     */
    public function requestCode(ForgotPasswordRequestCodeRequest $request): JsonResponse
    {
        $payload = $this->repo->requestPasswordResetCode(
            email: (string) $request->input('email'),
            ip: $request->ip(),
            userAgent: $request->userAgent()
        );

        $httpCode = (int) ($payload['_http_code'] ?? Response::HTTP_OK);
        unset($payload['_http_code']);

        return response()->json([
            'code' => $httpCode,
            'message' => __($payload['message_key'] ?? 'messages.auth.password_reset_code_sent'),
            'data' => $payload,
            'error' => null,
        ], $httpCode);
    }

    /**
     * POST /auth/password/forgot/confirm
     */
    public function confirm(ForgotPasswordConfirmRequest $request): JsonResponse
    {
        $payload = $this->repo->resetPasswordWithCode(
            email: (string) $request->input('email'),
            code: (string) $request->input('code'),
            password: (string) $request->input('password')
        );

        $httpCode = (int) ($payload['_http_code'] ?? Response::HTTP_OK);
        unset($payload['_http_code']);

        return response()->json([
            'code' => $httpCode,
            'message' => __($payload['message_key'] ?? 'messages.auth.password_reset_success'),
            'data' => $payload,
            'error' => null,
        ], $httpCode);
    }
}
