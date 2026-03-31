<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('verify.password_reset_mail.subject') }} | {{ $appName }}</title>
</head>
<body style="margin:0; padding:24px 12px; font-family: Arial, Helvetica, sans-serif; background-color:#f3f4f6; color:#111827;">

<span style="display:none !important; visibility:hidden; opacity:0; color:transparent; height:0; width:0; overflow:hidden;">
    {{ __('verify.password_reset_mail.subject') }}. {{ __('verify.password_reset_mail.line3', ['minutes' => $ttlMinutes]) }}
</span>

<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="max-width:640px; margin:0 auto;">
    <tr>
        <td>
            <div style="background:#ffffff; border:1px solid #e5e7eb; border-radius:16px; overflow:hidden; box-shadow:0 4px 14px rgba(0,0,0,0.06);">

                @if(!empty($logoUrl))
                    <div style="text-align:center; padding:24px 24px 12px 24px; background:#ffffff;">
                        <img src="{{ $logoUrl }}" alt="{{ $appName }} logo" height="40" style="height:40px; max-height:40px;">
                    </div>
                @endif

                <div style="padding: 12px 32px 32px 32px;">
                    <div style="display:inline-block; padding:6px 12px; border-radius:999px; background:#fef3c7; color:#92400e; font-size:12px; font-weight:700; letter-spacing:.2px; margin-bottom:16px;">
                        {{ __('verify.password_reset_mail.subject') }}
                    </div>

                    <h1 style="font-size:26px; line-height:1.25; font-weight:700; margin:0 0 16px 0; color:#111827;">
                        {{ __('verify.password_reset_mail.subject') }}
                    </h1>

                    <p style="margin:0 0 12px 0; font-size:15px; line-height:1.7; color:#374151;">
                        {{ __('verify.password_reset_mail.greeting', ['name' => $user->name]) }}
                    </p>

                    <p style="margin:0 0 20px 0; font-size:15px; line-height:1.7; color:#374151;">
                        {{ __('verify.password_reset_mail.line1') }}
                    </p>

                    <p style="margin:0 0 10px 0; font-size:14px; font-weight:600; color:#111827;">
                        {{ __('verify.password_reset_mail.code_is') }}
                    </p>

                    <div style="background:#f9fafb; border:1px dashed #cbd5e1; border-radius:12px; padding:24px; text-align:center; margin-bottom:20px;">
                        <div style="font-size:36px; line-height:1; font-weight:700; letter-spacing:8px; color:#111827; margin-bottom:10px; font-family: Arial, Helvetica, sans-serif;">
                            {{ $code }}
                        </div>
                        <div style="font-size:13px; color:#6b7280; letter-spacing:4px;">
                            {{ implode(' ', str_split($code)) }}
                        </div>
                    </div>

                    <p style="margin:0 0 18px 0; font-size:14px; line-height:1.6; color:#4b5563;">
                        {{ __('verify.password_reset_mail.line3', ['minutes' => $ttlMinutes]) }}
                    </p>

                    @if($ip || $userAgent || $requestedAt)
                        <div style="background:#f8fafc; border:1px solid #e5e7eb; border-left:4px solid #f59e0b; border-radius:10px; padding:14px 16px; margin:20px 0;">
                            <p style="margin:0 0 8px 0; font-size:13px; font-weight:700; color:#111827;">
                                {{ __('verify.password_reset_mail.security_header') }}
                            </p>
                            @if($requestedAt)
                                <div style="font-size:13px; color:#4b5563; margin-bottom:4px;">
                                    {{ __('verify.password_reset_mail.request_time') }}: {{ $requestedAt->timezone(config('app.timezone'))->format('Y-m-d H:i') }}
                                </div>
                            @endif
                            @if($ip)
                                <div style="font-size:13px; color:#4b5563; margin-bottom:4px;">
                                    <strong>IP:</strong> {{ $ip }}
                                </div>
                            @endif
                            @if($userAgent)
                                <div style="font-size:13px; color:#4b5563;">
                                    <strong>{{ __('verify.password_reset_mail.device') }}:</strong> {{ $userAgent }}
                                </div>
                            @endif
                        </div>
                    @endif

                    <p style="margin:20px 0 8px 0; font-size:14px; line-height:1.6; color:#374151;">
                        {{ __('verify.password_reset_mail.line4') }}
                    </p>

                    @isset($actionUrl)
                        <p style="margin:0 0 16px 0; font-size:14px; color:#6b7280;">
                            {{ __('verify.password_reset_mail.open_app') }}
                        </p>

                        <div style="text-align:center; margin: 24px 0 28px 0;">
                            <a href="{{ $actionUrl }}"
                               style="background:#d97706; color:#ffffff; padding:14px 24px; font-size:15px; font-weight:700; text-decoration:none; border-radius:10px; display:inline-block;">
                                {{ __('verify.password_reset_mail.open_app') }}
                            </a>
                        </div>
                    @endisset

                    <div style="border-top:1px solid #e5e7eb; padding-top:20px;">
                        <p style="margin:0 0 10px 0; font-size:14px; line-height:1.6; color:#374151;">
                            {{ __('verify.password_reset_mail.need_help') }}
                            @if($helpUrl)
                                <br><a href="{{ $helpUrl }}" style="color:#2563eb; text-decoration:none;">{{ $helpUrl }}</a>
                            @endif
                            @if($support)
                                <br>{{ __('verify.password_reset_mail.or_write') }} <a href="mailto:{{ $support }}" style="color:#2563eb; text-decoration:none;">{{ $support }}</a>
                            @endif
                        </p>

                        <p style="margin:20px 0 0 0; font-size:14px; line-height:1.6; color:#111827;">
                            {{ __('verify.password_reset_mail.salutation') }}<br>
                            <strong>{{ $appName }}</strong>
                        </p>
                    </div>
                </div>

                <div style="background:#f9fafb; border-top:1px solid #e5e7eb; padding:16px 24px; text-align:center; font-size:12px; line-height:1.6; color:#6b7280;">
                    {{ __('verify.password_reset_mail.subcopy') }}
                </div>
            </div>
        </td>
    </tr>
</table>

</body>
</html>
