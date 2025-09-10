<?php

namespace App\Repositories\Administration;

use App\Models\Administration\EmailVerification;
use App\Models\Administration\Tenant;
use App\Models\Administration\User;
use App\Notifications\VerifyEmailCode;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

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
                'user_id'     => $user->id,
                'purpose'     => $purpose,
                'code_hash'   => Hash::make($code),
                'expires_at'  => now()->addMinutes($ttlMinutes),
                'sent_at'     => now(),
                'max_attempts'=> (int) config('auth.verify.max_attempts', 5),
                'locale'      => app()->getLocale(),
                'ip'          => $ip,
                'user_agent'  => $ua,
            ]);

            // envía email (queue)
            $user->notify(new VerifyEmailCode($code, $ttlMinutes));

            // auditoría opcional
            app(AuditLogRepository::class)->log(
                actor: $user,
                event: 'email_verification.code_sent',
                subject: $rec,
                description: __('verify.sent'),
                changes: ['old'=>null,'new'=>null],
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

        if (! $rec) return false;
        if ($rec->isExpired()) return false;
        if ($rec->attempts >= $rec->max_attempts) return false;

        $ok = Hash::check($code, $rec->code_hash);

        $rec->attempts++;
        if ($ok) {
            DB::transaction(function () use ($rec, $user) {
                $rec->consumed_at = now();
                $rec->save();

                // marca email verificado y activa cuenta (si la desactivas al registrar)
                $user->email_verified_at = now();
                $user->is_active = true; // opcional
                $user->save();

                app(AuditLogRepository::class)->log(
                    actor: $user,
                    event: 'email_verification.verified',
                    subject: $user,
                    description: __('verify.verified'),
                    changes: ['old'=>null,'new'=>['email_verified_at'=>$user->email_verified_at]],
                    tenantId: Tenant::current()?->id
                );
            });
        } else {
            $rec->save();
            app(AuditLogRepository::class)->log(
                actor: $user,
                event: 'email_verification.failed',
                subject: $user,
                description: __('verify.failed'),
                changes: ['old'=>null,'new'=>['attempts'=>$rec->attempts]],
                tenantId: Tenant::current()?->id
            );
        }

        return $ok;
    }

    public function resendCode(User $user, string $purpose = 'verify_email'): void
    {
        // Rate limit simple por última emisión
        $last = EmailVerification::query()
            ->where('user_id', $user->id)
            ->where('purpose', $purpose)
            ->orderByDesc('sent_at')
            ->first();

        $minGap = (int) config('auth.verify.resend_cooldown_seconds', 60);
        if ($last && $last->sent_at && now()->diffInSeconds($last->sent_at) < $minGap) {
            abort(429, __('verify.too_soon'));
        }

        $this->issueCode($user, $purpose, request()->ip(), request()->userAgent());
    }
}
