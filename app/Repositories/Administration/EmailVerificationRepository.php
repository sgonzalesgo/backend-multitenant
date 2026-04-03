<?php

namespace App\Repositories\Administration;

use App\Models\Administration\EmailVerification;
use App\Models\Administration\Tenant;
use App\Models\Administration\User;
use App\Notifications\VerifyEmailCode;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class EmailVerificationRepository
{
    public function issueCode(User $user, string $purpose = 'verify_email', ?string $ip = null, ?string $ua = null): EmailVerification
    {
        $ttlMinutes = (int) config('auth.verify.ttl_minutes', 15);
        $code = (string) random_int(100000, 999999);

        return DB::transaction(function () use ($user, $purpose, $ttlMinutes, $code, $ip, $ua) {
            // elimina códigos previos no consumidos de ese propósito
            EmailVerification::query()
                ->where('user_id', $user->id)
                ->where('purpose', $purpose)
                ->whereNull('consumed_at')
                ->delete();

            $rec = EmailVerification::create([
                'user_id'      => $user->id,
                'purpose'      => $purpose,
                'code_hash'    => Hash::make($code),
                'expires_at'   => now()->addMinutes($ttlMinutes),
                'sent_at'      => now(),
                'max_attempts' => (int) config('auth.verify.max_attempts', 5),
                'locale'       => app()->getLocale(),
                'ip'           => $ip,
                'user_agent'   => $ua,
            ]);

            // envía email (queue)
            $user->notify(new VerifyEmailCode(
                code6: $code,
                ttlMinutes: $ttlMinutes,
                purpose: $purpose,
                ip: $ip,
                userAgent: $ua,
                requestedAt: now()
            ));

            // auditoría opcional
            app(AuditLogRepository::class)->log(
                actor: $user,
                event: 'email_verification.code_sent',
                subject: $rec,
                description: __('verify.sent'),
                changes: ['old' => null, 'new' => ['purpose' => $purpose]],
                tenantId: Tenant::current()?->id
            );

            return $rec;
        });
    }

    public function verifyCode(User $user, string $code, string $purpose = 'verify_email'): bool
    {
        $rec = EmailVerification::query()
            ->where('user_id', $user->id)
            ->where('purpose', $purpose)
            ->whereNull('consumed_at')
            ->orderByDesc('created_at')
            ->first();

        if (!$rec) {
            return false;
        }

        if ($rec->isExpired()) {
            return false;
        }

        if ($rec->attempts >= $rec->max_attempts) {
            return false;
        }

        $ok = Hash::check($code, $rec->code_hash);

        $rec->attempts++;

        if ($ok) {
            DB::transaction(function () use ($rec, $user, $purpose) {
                $rec->consumed_at = now();
                $rec->save();

                if ($purpose === 'verify_email') {
                    $user->email_verified_at = now();
                    $user->status = 'active';
                    $user->save();

                    app(AuditLogRepository::class)->log(
                        actor: $user,
                        event: 'email_verification.verified',
                        subject: $user,
                        description: __('verify.verified'),
                        changes: ['old' => null, 'new' => ['email_verified_at' => $user->email_verified_at]],
                        tenantId: Tenant::current()?->id
                    );
                } else {
                    app(AuditLogRepository::class)->log(
                        actor: $user,
                        event: 'email_verification.code_verified',
                        subject: $rec,
                        description: __('verify.code_verified'),
                        changes: ['old' => null, 'new' => ['purpose' => $purpose]],
                        tenantId: Tenant::current()?->id
                    );
                }
            });
        } else {
            $rec->save();

            app(AuditLogRepository::class)->log(
                actor: $user,
                event: 'email_verification.failed',
                subject: $user,
                description: __('verify.failed'),
                changes: ['old' => null, 'new' => ['attempts' => $rec->attempts, 'purpose' => $purpose]],
                tenantId: Tenant::current()?->id
            );
        }

        return $ok;
    }

    public function resendCode(User $user, string $purpose = 'verify_email'): void
    {
        $last = EmailVerification::query()
            ->where('user_id', $user->id)
            ->where('purpose', $purpose)
            ->orderByDesc('sent_at')
            ->first();

        $minGap = (int) config('auth.verify.resend_cooldown_seconds', 60);

        if ($last && $last->sent_at) {
            $secondsSinceLastSend = $last->sent_at->diffInSeconds(now(), false);

            if ($secondsSinceLastSend >= 0 && $secondsSinceLastSend < $minGap) {
                abort(429, __('verify.too_soon'));
            }
        }

        $this->issueCode($user, $purpose, request()->ip(), request()->userAgent());
    }

    public function cooldownRemainingSeconds(User $user, string $purpose = 'verify_email'): int
    {
        $last = EmailVerification::query()
            ->where('user_id', $user->id)
            ->where('purpose', $purpose)
            ->orderByDesc('sent_at')
            ->first();

        if (!$last || !$last->sent_at) {
            return 0;
        }

        $minGap = (int) config('auth.verify.resend_cooldown_seconds', 60);

        $secondsSinceLastSend = $last->sent_at->diffInSeconds(now(), false);

        if ($secondsSinceLastSend < 0) {
            return 0;
        }

        return max(0, $minGap - $secondsSinceLastSend);
    }

    public function activeVerificationMeta(User $user, string $purpose = 'verify_email'): ?array
    {
        $rec = EmailVerification::query()
            ->where('user_id', $user->id)
            ->where('purpose', $purpose)
            ->whereNull('consumed_at')
            ->orderByDesc('created_at')
            ->first();

        if (!$rec) {
            return null;
        }

        return [
            'expires_at' => optional($rec->expires_at)?->toIso8601String(),
            'attempts' => (int) $rec->attempts,
            'max_attempts' => (int) $rec->max_attempts,
            'cooldown_remaining' => $this->cooldownRemainingSeconds($user, $purpose),
        ];
    }
}
