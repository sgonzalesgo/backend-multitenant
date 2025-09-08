<?php

namespace App\Http\Controllers\Administration;

use App\Http\Controllers\Controller;
use App\Models\Administration\User;
use App\Repositories\Administration\EmailVerificationRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EmailVerificationController extends Controller
{
    public function __construct(private readonly EmailVerificationRepository $repo) {}

    // POST /auth/verify/request-code
    public function requestCode(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required','email'],
        ]);

        // Por privacidad, devolvemos 200 siempre, exista o no el email
        $user = User::query()->where('email', $request->input('email'))->first();
        if ($user && ! $user->email_verified_at) {
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
            'email' => ['required','email'],
            'code'  => ['required','digits:6'],
        ]);

        $user = User::query()->where('email', $data['email'])->firstOrFail();

        if ($user->email_verified_at) {
            return response()->json([
                'code' => 200,
                'message' => __('verify.already'),
                'data' => null,
                'error' => null,
            ], Response::HTTP_OK);
        }

        $ok = $this->repo->verifyCode($user, $data['code']);

        return response()->json([
            'code'    => $ok ? 200 : 422,
            'message' => $ok ? __('verify.ok') : __('verify.invalid'),
            'data'    => null,
            'error'   => $ok ? null : 'InvalidCode',
        ], $ok ? Response::HTTP_OK : Response::HTTP_UNPROCESSABLE_ENTITY);
    }
}
