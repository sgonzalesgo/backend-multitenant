<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Authentication Defaults
    |--------------------------------------------------------------------------
    |
    | This option defines the default authentication "guard" and password
    | reset "broker" for your application. You may change these values
    | as required, but they're a perfect start for most applications.
    |
    */

    'defaults' => [
        'guard' => env('AUTH_GUARD', 'api'),
        'passwords' => env('AUTH_PASSWORD_BROKER', 'users'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Authentication Guards
    |--------------------------------------------------------------------------
    |
    | Next, you may define every authentication guard for your application.
    | Of course, a great default configuration has been defined for you
    | which utilizes session storage plus the Eloquent user provider.
    |
    | Supported: "session"
    |
    */

    'guards' => [
        'api' => [
            'driver' => 'passport',
            'provider' => 'users',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | User Providers
    |--------------------------------------------------------------------------
    |
    | All authentication guards have a user provider, which defines how the
    | users are actually retrieved out of your database or other storage
    | system used by the application.
    |
    | Supported: "database", "eloquent"
    |
    */

    'providers' => [
        'users' => [
            'driver' => 'eloquent',
            'model' => env('AUTH_MODEL', \App\Models\Administration\User::class),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Resetting Passwords
    |--------------------------------------------------------------------------
    |
    | These configuration options specify the behavior of Laravel's password
    | reset functionality.
    |
    */

    'passwords' => [
        'users' => [
            'provider' => 'users',
            'table' => env('AUTH_PASSWORD_RESET_TOKEN_TABLE', 'password_reset_tokens'),
            'expire' => 60,
            'throttle' => 60,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Password Confirmation Timeout
    |--------------------------------------------------------------------------
    |
    | Number of seconds before a password confirmation window expires.
    |
    */

    'password_timeout' => env('AUTH_PASSWORD_TIMEOUT', 10800),

    /*
    |--------------------------------------------------------------------------
    | Auth Cookies
    |--------------------------------------------------------------------------
    |
    | Names for auth cookies used by the API + BFF flow.
    | The browser never needs direct token access; the backend/BFF will
    | translate cookies into Authorization headers when needed.
    |
    */

    'cookie' => env('AUTH_ACCESS_COOKIE', 'access_token'),
    'refresh_cookie' => env('AUTH_REFRESH_COOKIE', 'refresh_token'),
    'tenant_cookie' => env('AUTH_TENANT_COOKIE', 'tenant_id'),

    /*
    |--------------------------------------------------------------------------
    | Token Lifetimes
    |--------------------------------------------------------------------------
    |
    | Centralized token durations so controllers/repositories do not depend
    | on scattered fallback values.
    |
    */

    'tokens' => [
        'access_minutes' => (int) env('AUTH_ACCESS_TOKEN_MINUTES', 15),
        'refresh_days' => (int) env('AUTH_REFRESH_TOKEN_DAYS', 30),
        'impersonation_minutes' => (int) env('AUTH_IMPERSONATION_TOKEN_MINUTES', 60),
        'backup_minutes' => (int) env('AUTH_BACKUP_TOKEN_MINUTES', 120),
    ],

];
