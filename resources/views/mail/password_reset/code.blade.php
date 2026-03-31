<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <title>{{ __('verify.password_reset_mail.subject') }} | {{ $appName }}</title>
</head>
<body style="margin:0; padding:0; font-family: Arial, Helvetica, sans-serif; background-color:#f9fafb; color:#111827;">

<div style="max-width: 600px; margin: 0 auto; background:#ffffff; border-radius:8px; overflow:hidden; box-shadow:0 2px 6px rgba(0,0,0,0.05);">

    @if(!empty($logoUrl))
        <div style="text-align:center; padding:20px; border-bottom:1px solid #e5e7eb;">
            <img src="{{ $logoUrl }}" alt="{{ $appName }} logo" height="40" style="height:40px; max-height:40px;">
        </div>
    @endif

    <div style="padding: 30px;">
        <h1 style="font-size:20px; font-weight:700; margin:0 0 16px 0; color:#111827;">
            {{ __('verify.password_reset_mail.subject') }}
        </h1>

        <p style="margin:0 0 12px 0; font-size:15px; line-height:1.5;">
            {{ __('verify.password_reset_mail.greeting', ['name' => $user->name]) }}
        </p>

        <p style="margin:0 0 24px 0; font-size:15px; line-height:1.5;">
            {{ __('verify.password_reset_mail.line1') }}
        </p>

        <p style="margin:0 0 12px 0; font-size:15px; line-height:1.5;">
            {{ __('verify.password_reset_mail.code_is') }}
        </p>

        <div style="background:#f3f4f6; border-radius:6px; padding:20px; text-align:center; margin-bottom:24px;">
            <div style="font-size:32px; font-weight:700; letter-spacing:6px; color:#111827; margin-bottom:8px;">
                {{ $code }}
            </div>
            <div style="font-size:13px; color:#6b7280; letter-spacing:3px;">
                {{ implode(' ', str_split($code)) }}
            </div>
        </div>

        <p style="margin:0 0 16px 0; font-size:14px; color:#374151;">
            {{ __('verify.password_reset_mail.line3', ['minutes' => $ttlMinutes]) }}
        </p>

        @if($ip || $userAgent || $requestedAt)
            <div style="background:#f9fafb; border-left:4px solid #3b82f6; padding:12px 16px; font-size:13px; color:#374151; margin:16px 0;">
                <p style="margin:0 0 6px 0; font-weight:600;">{{ __('verify.password_reset_mail.security_header') }}</p>
                @if($requestedAt)
                    <div>{{ __('verify.password_reset_mail.request_time') }}: {{ $requestedAt->timezone(config('app.timezone'))->format('Y-m-d H:i') }}</div>
                @endif
                @if($ip)
                    <div><strong>IP:</strong> {{ $ip }}</div>
                @endif
                @if($userAgent)
                    <div><strong>{{ __('verify.password_reset_mail.device') }}:</strong> {{ $userAgent }}</div>
                @endif
            </div>
        @endif

        <p style="margin:0 0 24px 0; font-size:14px; color:#374151;">
            {{ __('verify.password_reset_mail.line4') }}
        </p>

        @isset($actionUrl)
            <div style="text-align:center; margin: 32px 0;">
                <a href="{{ $actionUrl }}"
                   style="background:#2563eb; color:#ffffff; padding:12px 24px; font-size:15px; font-weight:600; text-decoration:none; border-radius:6px; display:inline-block;">
                    {{ __('verify.password_reset_mail.open_app') }}
                </a>
            </div>
        @endisset

        <p style="margin:0 0 12px 0; font-size:14px; color:#374151;">
            {{ __('verify.password_reset_mail.need_help') }}
            @if($helpUrl)
                <br><a href="{{ $helpUrl }}" style="color:#2563eb; text-decoration:none;">{{ $helpUrl }}</a>
            @endif
            @if($support)
                <br>{{ __('verify.password_reset_mail.or_write') }} <a href="mailto:{{ $support }}" style="color:#2563eb;">{{ $support }}</a>
            @endif
        </p>

        <p style="margin-top:32px; font-size:14px; color:#111827;">
            {{ __('verify.password_reset_mail.salutation') }}<br>
            {{ $appName }}
        </p>
    </div>

    <div style="background:#f9fafb; padding:16px; text-align:center; font-size:12px; color:#6b7280;">
        {{ __('verify.password_reset_mail.subcopy') }}
    </div>

</div>
</body>
</html>
