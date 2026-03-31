{{ __('verify.password_reset_mail.subject') }} - {{ $appName }}

{{ __('verify.password_reset_mail.greeting', ['name' => $user->name]) }}
{{ __('verify.password_reset_mail.line1') }}

{{ __('verify.password_reset_mail.code_is') }} {{ $code }}
{{ __('verify.password_reset_mail.line3', ['minutes' => $ttlMinutes]) }}

@if($ip || $userAgent || $requestedAt)
    {{ __('verify.password_reset_mail.security_header') }}
    @if($requestedAt)
        {{ __('verify.password_reset_mail.request_time') }}: {{ $requestedAt->timezone(config('app.timezone'))->format('Y-m-d H:i') }}
    @endif
    @if($ip)
        IP: {{ $ip }}
    @endif
    @if($userAgent)
        {{ __('verify.password_reset_mail.device') }}: {{ $userAgent }}
    @endif
@endif

{{ __('verify.password_reset_mail.line4') }}

{{ __('verify.password_reset_mail.open_app') }}: {{ $actionUrl }}

{{ __('verify.password_reset_mail.need_help') }}
@if($helpUrl)
    {{ $helpUrl }}
@endif
@if($support)
    {{ __('verify.password_reset_mail.or_write') }} {{ $support }}
@endif

{{ __('verify.password_reset_mail.salutation') }}
{{ $appName }}
