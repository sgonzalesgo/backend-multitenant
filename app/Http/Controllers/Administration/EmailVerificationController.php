<?php

namespace App\Http\Controllers\Administration;

use App\Http\Controllers\Controller;
use App\Models\Administration\User;
use App\Repositories\Administration\EmailVerificationRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Symfony\Component\HttpFoundation\Response;

class EmailVerificationController extends Controller
{
    public function __construct(
        private readonly EmailVerificationRepository $repo,
    ) {}

    // POST /auth/verify/request-code
    public function requestCode(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $user = User::query()->where('email', $request->input('email'))->first();

        $resent = false;
        $cooldownRemaining = 0;
        $verification = null;

        if ($user && !$user->email_verified_at) {
            $cooldownRemaining = $this->repo->cooldownRemainingSeconds($user);

            if ($cooldownRemaining <= 0) {
                $this->repo->issueCode($user, 'verify_email', $request->ip(), $request->userAgent());
                $resent = true;
            }

            $verification = $this->repo->activeVerificationMeta($user);
        }

        return response()->json([
            'code' => 200,
            'message' => __('verify.sent_ok'),
            'data' => [
                'status' => 'PENDING_VERIFICATION',
                'resent' => $resent,
                'cooldown_remaining' => $cooldownRemaining,
                'verification' => $verification,
            ],
            'error' => null,
        ], Response::HTTP_OK);
    }


    public function confirm(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'code'  => ['required', 'digits:6'],
        ]);

        /** @var User $user */
        $user = User::query()->where('email', $data['email'])->firstOrFail();

        if ($user->email_verified_at) {
            $user = $user->refresh();

            return response()->json([
                'code' => Response::HTTP_OK,
                'message' => __('verify.already'),
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'avatar' => $user->avatar,
                        'locale' => $user->locale,
                        'is_active' => (bool) $user->is_active,
                        'email_verified_at' => optional($user->email_verified_at)?->toIso8601String(),
                        'created_at' => optional($user->created_at)?->toIso8601String(),
                        'updated_at' => optional($user->updated_at)?->toIso8601String(),
                    ],
                ],
                'error' => null,
            ], Response::HTTP_OK);
        }

        $ok = $this->repo->verifyCode($user, $data['code']);

        if (! $ok) {
            return response()->json([
                'code'    => Response::HTTP_UNPROCESSABLE_ENTITY,
                'message' => __('verify.invalid'),
                'data'    => null,
                'error'   => 'InvalidCode',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $user = $user->refresh();

        return response()->json([
            'code'    => Response::HTTP_OK,
            'message' => __('verify.ok'),
            'data'    => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'avatar' => $user->avatar,
                    'locale' => $user->locale,
                    'is_active' => (bool) $user->is_active,
                    'email_verified_at' => optional($user->email_verified_at)?->toIso8601String(),
                    'created_at' => optional($user->created_at)?->toIso8601String(),
                    'updated_at' => optional($user->updated_at)?->toIso8601String(),
                ],
            ],
            'error'   => null,
        ], Response::HTTP_OK);
    }

}
