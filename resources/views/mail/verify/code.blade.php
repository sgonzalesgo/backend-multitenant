{{--@component('mail::message')--}}
{{--    --}}{{-- Header con logo --}}
{{--    @if(!empty($logoUrl))--}}
{{--        <table role="presentation" width="100%" style="margin-bottom: 8px;">--}}
{{--            <tr>--}}
{{--                <td align="center">--}}
{{--                    <img src="{{ $logoUrl }}" alt="{{ $appName }} logo" height="40" style="height:40px; max-height:40px;">--}}
{{--                </td>--}}
{{--            </tr>--}}
{{--        </table>--}}
{{--    @endif--}}

{{--    --}}{{-- Título --}}
{{--    # {{ __('verify.mail.subject') }}--}}

{{--    --}}{{-- Saludo + introducción --}}
{{--    {{ __('verify.mail.greeting', ['name' => $user->name]) }}--}}

{{--    {{ __('verify.mail.line1') }}--}}

{{--    --}}{{-- Código grande dentro de panel --}}
{{--    @component('mail::panel')--}}
{{--        <div style="text-align:center;">--}}
{{--            <div style="font-size: 32px; font-weight: 700; letter-spacing: 6px; margin-bottom: 6px;">--}}
{{--                {{ $code }}--}}
{{--            </div>--}}
{{--            <div style="font-size: 12px; color: #6b7280;">--}}
{{--                {{ implode(' ', str_split($code)) }}--}}
{{--            </div>--}}
{{--        </div>--}}
{{--    @endcomponent--}}

{{--    --}}{{-- Vencimiento --}}
{{--    {{ __('verify.mail.line3', ['minutes' => $ttlMinutes]) }}--}}

{{--    --}}{{-- Datos de seguridad opcionales --}}
{{--    @if($ip || $userAgent || $requestedAt)--}}
{{--        > {{ __('verify.mail.security_header') }}--}}
{{--        > @if($requestedAt) {{ __('verify.mail.request_time') }}: {{ $requestedAt->timezone(config('app.timezone'))->format('Y-m-d H:i') }} @endif--}}
{{--        > @if($ip) **IP:** {{ $ip }} @endif--}}
{{--        > @if($userAgent) **{{ __('verify.mail.device') }}:** {{ $userAgent }} @endif--}}
{{--    @endif--}}

{{--    --}}{{-- Nota de seguridad + botón opcional --}}
{{--    {{ __('verify.mail.line4') }}--}}

{{--    @isset($appUrl)--}}
{{--        @component('mail::button', ['url' => $appUrl])--}}
{{--            {{ __('verify.mail.open_app') }}--}}
{{--        @endcomponent--}}
{{--    @endisset--}}

{{--    --}}{{-- Ayuda / soporte --}}
{{--    {{ __('verify.mail.need_help') }}--}}
{{--    @if($helpUrl)--}}
{{--        [{{ $helpUrl }}]({{ $helpUrl }})--}}
{{--    @endif--}}
{{--    @if($support)--}}
{{--        <br>{{ __('verify.mail.or_write') }} <a href="mailto:{{ $support }}">{{ $support }}</a>--}}
{{--    @endif--}}

{{--    --}}{{-- Firma --}}
{{--    {{ __('verify.mail.salutation') }}--}}

{{--    @slot('subcopy')--}}
{{--        {{ __('verify.mail.subcopy') }}--}}
{{--    @endslot--}}
{{--@endcomponent--}}

    <!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>{{ __('verify.mail.subject') }} | {{ $appName }}</title>
</head>
<body style="margin:0; padding:0; font-family: Arial, Helvetica, sans-serif; background-color:#f9fafb; color:#111827;">

<div style="max-width: 600px; margin: 0 auto; background:#ffffff; border-radius:8px; overflow:hidden; box-shadow:0 2px 6px rgba(0,0,0,0.05);">

    {{-- Header con logo --}}
    @if(!empty($logoUrl))
        <div style="text-align:center; padding:20px; border-bottom:1px solid #e5e7eb;">
            <img src="{{ $logoUrl }}" alt="{{ $appName }} logo" height="40" style="height:40px; max-height:40px;">
        </div>
    @endif

    {{-- Contenido principal --}}
    <div style="padding: 30px;">

        {{-- Título --}}
        <h1 style="font-size:20px; font-weight:700; margin:0 0 16px 0; color:#111827;">
            {{ __('verify.mail.subject') }}
        </h1>

        {{-- Saludo + introducción --}}
        <p style="margin:0 0 12px 0; font-size:15px; line-height:1.5;">
            {{ __('verify.mail.greeting', ['name' => $user->name]) }}
        </p>
        <p style="margin:0 0 24px 0; font-size:15px; line-height:1.5;">
            {{ __('verify.mail.line1') }}
        </p>

        {{-- Código grande --}}
        <div style="background:#f3f4f6; border-radius:6px; padding:20px; text-align:center; margin-bottom:24px;">
            <div style="font-size:32px; font-weight:700; letter-spacing:6px; color:#111827; margin-bottom:8px;">
                {{ $code }}
            </div>
            <div style="font-size:13px; color:#6b7280; letter-spacing:3px;">
                {{ implode(' ', str_split($code)) }}
            </div>
        </div>

        {{-- Expiración --}}
        <p style="margin:0 0 16px 0; font-size:14px; color:#374151;">
            {{ __('verify.mail.line3', ['minutes' => $ttlMinutes]) }}
        </p>

        {{-- Datos de seguridad opcionales --}}
        @if($ip || $userAgent || $requestedAt)
            <div style="background:#f9fafb; border-left:4px solid #3b82f6; padding:12px 16px; font-size:13px; color:#374151; margin:16px 0;">
                <p style="margin:0 0 6px 0; font-weight:600;">{{ __('verify.mail.security_header') }}</p>
                @if($requestedAt)
                    <div>{{ __('verify.mail.request_time') }}: {{ $requestedAt->timezone(config('app.timezone'))->format('Y-m-d H:i') }}</div>
                @endif
                @if($ip)
                    <div><strong>IP:</strong> {{ $ip }}</div>
                @endif
                @if($userAgent)
                    <div><strong>{{ __('verify.mail.device') }}:</strong> {{ $userAgent }}</div>
                @endif
            </div>
        @endif

        {{-- Nota de seguridad --}}
        <p style="margin:0 0 24px 0; font-size:14px; color:#374151;">
            {{ __('verify.mail.line4') }}
        </p>

        {{-- Botón --}}
        @isset($appUrl)
            <div style="text-align:center; margin: 32px 0;">
                <a href="{{ $actionUrl }}"
                   style="background:#2563eb; color:#ffffff; padding:12px 24px; font-size:15px; font-weight:600; text-decoration:none; border-radius:6px; display:inline-block;">
                    {{ __('verify.mail.open_app') }}
                </a>
            </div>
        @endisset

        {{-- Ayuda / soporte --}}
        <p style="margin:0 0 12px 0; font-size:14px; color:#374151;">
            {{ __('verify.mail.need_help') }}
            @if($helpUrl)
                <br><a href="{{ $helpUrl }}" style="color:#2563eb; text-decoration:none;">{{ $helpUrl }}</a>
            @endif
            @if($support)
                <br>{{ __('verify.mail.or_write') }} <a href="mailto:{{ $support }}" style="color:#2563eb;">{{ $support }}</a>
            @endif
        </p>

        {{-- Firma --}}
        <p style="margin-top:32px; font-size:14px; color:#111827;">
            {{ __('verify.mail.salutation') }}
        </p>
    </div>

    {{-- Subcopy --}}
    <div style="background:#f9fafb; padding:16px; text-align:center; font-size:12px; color:#6b7280;">
        {{ __('verify.mail.subcopy') }}
    </div>

</div>
</body>
</html>

