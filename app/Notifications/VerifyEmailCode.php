<?php
//
//namespace App\Notifications;
//
//use Illuminate\Bus\Queueable;
//use Illuminate\Contracts\Queue\ShouldQueue;
//use Illuminate\Notifications\Messages\MailMessage;
//use Illuminate\Notifications\Notification;
//use Spatie\Multitenancy\Jobs\NotTenantAware;
//
//class VerifyEmailCode extends Notification implements ShouldQueue, NotTenantAware
//{
//    use Queueable;
//
//    public function __construct(
//        public string $code6,
//        public int $ttlMinutes
//    ) {}
//
//    public function via($notifiable): array
//    {
//        return ['mail'];
//    }
//
//    public function toMail($notifiable): MailMessage
//    {
//        $line1 = __('verify.mail.line1');
//        $line2 = __('verify.mail.line2', ['code' => $this->code6]);
//        $line3 = __('verify.mail.line3', ['minutes' => $this->ttlMinutes]);
//
//        return (new MailMessage)
//            ->subject(__('verify.mail.subject'))
//            ->greeting(__('verify.mail.greeting', ['name' => $notifiable->name]))
//            ->line($line1)
//            ->line($line2)
//            ->line($line3)
//            ->salutation(__('verify.mail.salutation'));
//    }
//}


namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Spatie\Multitenancy\Jobs\NotTenantAware;

class VerifyEmailCode extends Notification implements ShouldQueue, NotTenantAware
{
    use Queueable;

    public function __construct(
        public string $code6,
        public int $ttlMinutes,
        public ?string $ip = null,
        public ?string $userAgent = null,
        public ?\Carbon\CarbonInterface $requestedAt = null
    ) {}

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        // Puedes tomar valores de brand config, con fallback
        $appName   = config('app.name');
        $appUrl    = config('app.url');
        $logoUrl   = config('brand.mail_logo_url');     // opcional
        $helpUrl   = config('brand.help_url', $appUrl); // opcional
        $support   = config('brand.support_email', config('mail.from.address'));

        return (new MailMessage)
            ->subject(__('verify.mail.subject').' | '.$appName)
            // HTML (Markdown)
            ->markdown('mail.verify.code', [
                'user'        => $notifiable,
                'code'        => $this->code6,
                'ttlMinutes'  => $this->ttlMinutes,
                'ip'          => $this->ip,
                'userAgent'   => $this->userAgent,
                'requestedAt' => $this->requestedAt,
                'appName'     => $appName,
                'appUrl'      => $appUrl,
                'logoUrl'     => $logoUrl,
                'helpUrl'     => $helpUrl,
                'support'     => $support,
            ])
            // Texto plano dedicado
            ->text('mail.verify.code_plain', [
                'user'        => $notifiable,
                'code'        => $this->code6,
                'ttlMinutes'  => $this->ttlMinutes,
                'ip'          => $this->ip,
                'userAgent'   => $this->userAgent,
                'requestedAt' => $this->requestedAt,
                'appName'     => $appName,
                'appUrl'      => $appUrl,
                'helpUrl'     => $helpUrl,
                'support'     => $support,
            ]);
    }
}
