<?php

namespace App\Notifications;

use Carbon\CarbonInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Spatie\Multitenancy\Jobs\NotTenantAware;

class VerifyEmailCode extends Notification implements ShouldQueue, NotTenantAware
{
    use Queueable;

    public function __construct(
        public string $code6,
        public int $ttlMinutes,
        public string $purpose = 'verify_email',
        public ?string $ip = null,
        public ?string $userAgent = null,
        public ?CarbonInterface $requestedAt = null
    ) {}

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $appName = config('app.name');
        $appUrl = rtrim((string) config('app.frontend_url'), '/');
        $logoUrl = config('brand.mail_logo_url');
        $helpUrl = config('brand.help_url', $appUrl);
        $support = config('brand.support_email', config('mail.from.address'));

        $subjectKey = $this->purpose === 'forgot_password'
            ? 'verify.password_reset_mail.subject'
            : 'verify.mail.subject';

        $markdownView = $this->purpose === 'forgot_password'
            ? 'mail.password_reset.code'
            : 'mail.verify.code';

        $textView = $this->purpose === 'forgot_password'
            ? 'mail.password_reset.code_plain'
            : 'mail.verify.code_plain';

        $actionPath = $this->purpose === 'forgot_password'
            ? '/forgot-password'
            : '/verify/email';

        $actionUrl = $appUrl.$actionPath
            .'?email='.urlencode((string) $notifiable->email)
            .'&code='.urlencode($this->code6);

        return (new MailMessage)
            ->subject(__($subjectKey).' | '.$appName)
            ->markdown($markdownView, [
                'user'        => $notifiable,
                'code'        => $this->code6,
                'ttlMinutes'  => $this->ttlMinutes,
                'purpose'     => $this->purpose,
                'ip'          => $this->ip,
                'userAgent'   => $this->userAgent,
                'requestedAt' => $this->requestedAt,
                'appName'     => $appName,
                'appUrl'      => $appUrl,
                'logoUrl'     => $logoUrl,
                'helpUrl'     => $helpUrl,
                'support'     => $support,
                'actionUrl'   => $actionUrl,
            ])
            ->text($textView, [
                'user'        => $notifiable,
                'code'        => $this->code6,
                'ttlMinutes'  => $this->ttlMinutes,
                'purpose'     => $this->purpose,
                'ip'          => $this->ip,
                'userAgent'   => $this->userAgent,
                'requestedAt' => $this->requestedAt,
                'appName'     => $appName,
                'appUrl'      => $appUrl,
                'helpUrl'     => $helpUrl,
                'support'     => $support,
                'actionUrl'   => $actionUrl,
            ]);
    }
}
