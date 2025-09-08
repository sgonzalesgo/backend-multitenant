@component('mail::message')
    {{-- Header con logo --}}
    @if(!empty($logoUrl))
        <table role="presentation" width="100%" style="margin-bottom: 8px;">
            <tr>
                <td align="center">
                    <img src="{{ $logoUrl }}" alt="{{ $appName }} logo" height="40" style="height:40px; max-height:40px;">
                </td>
            </tr>
        </table>
    @endif

    {{-- Título --}}
    # {{ __('verify.mail.subject') }}

    {{-- Saludo + introducción --}}
    {{ __('verify.mail.greeting', ['name' => $user->name]) }}

    {{ __('verify.mail.line1') }}

    {{-- Código grande dentro de panel --}}
    @component('mail::panel')
        <div style="text-align:center;">
            <div style="font-size: 32px; font-weight: 700; letter-spacing: 6px; margin-bottom: 6px;">
                {{ $code }}
            </div>
            <div style="font-size: 12px; color: #6b7280;">
                {{ implode(' ', str_split($code)) }}
            </div>
        </div>
    @endcomponent

    {{-- Vencimiento --}}
    **{{ __('verify.mail.line3', ['minutes' => $ttlMinutes]) }}**

    {{-- Datos de seguridad opcionales --}}
    @if($ip || $userAgent || $requestedAt)
        > {{ __('verify.mail.security_header') }}
        > @if($requestedAt) **{{ __('verify.mail.request_time') }}:** {{ $requestedAt->timezone(config('app.timezone'))->format('Y-m-d H:i') }} @endif
        > @if($ip) **IP:** {{ $ip }} @endif
        > @if($userAgent) **{{ __('verify.mail.device') }}:** {{ $userAgent }} @endif
    @endif

    {{-- Nota de seguridad + botón opcional --}}
    {{ __('verify.mail.line4') }}

    @isset($appUrl)
        @component('mail::button', ['url' => $appUrl])
            {{ __('verify.mail.open_app') }}
        @endcomponent
    @endisset

    {{-- Ayuda / soporte --}}
    {{ __('verify.mail.need_help') }}
    @if($helpUrl)
        [{{ $helpUrl }}]({{ $helpUrl }})
    @endif
    @if($support)
        <br>{{ __('verify.mail.or_write') }} <a href="mailto:{{ $support }}">{{ $support }}</a>
    @endif

    {{-- Firma --}}
    {{ __('verify.mail.salutation') }}

    @slot('subcopy')
        {{ __('verify.mail.subcopy') }}
    @endslot
@endcomponent
