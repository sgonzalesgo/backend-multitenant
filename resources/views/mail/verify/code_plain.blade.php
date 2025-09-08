{{-- resources/views/mail/verify/code_plain.blade.php --}}

{{ __('verify.mail.subject') }} - {{ $appName }}

{{ __('verify.mail.greeting', ['name' => $user->name]) }}
{{ __('verify.mail.line1') }}

{{ __('verify.mail.code_is') }} {{ $code }}
{{ __('verify.mail.line3', ['minutes' => $ttlMinutes]) }}

@if($ip || $userAgent || $requestedAt)
    {{ __('verify.mail.security_header') }}
    @if($requestedAt)
        {{ __('verify.mail.request_time') }}: {{ $requestedAt->timezone(config('app.timezone'))->format('Y-m-d H:i') }}
    @endif
    @if($ip)
        IP: {{ $ip }}
    @endif
    @if($userAgent)
        {{ __('verify.mail.device') }}: {{ $userAgent }}
    @endif
@endif

{{ __('verify.mail.line4') }}

{{ __('verify.mail.open_app') }}: {{ $appUrl }}

{{ __('verify.mail.need_help') }}
@if($helpUrl)
    {{ $helpUrl }}
@endif
@if($support)
    {{ __('verify.mail.or_write') }} {{ $support }}
@endif

{{ __('verify.mail.salutation') }}
{{ $appName }}
