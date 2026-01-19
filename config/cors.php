<?php


return [

    /*
    |--------------------------------------------------------------------------
    | CORS Paths
    |--------------------------------------------------------------------------
    |
    | Incluye las rutas donde tu frontend llamará al backend.
    | Si tu API está bajo /api, deja api/*.
    | Si tu auth está bajo /auth, deja auth/*.
    | broadcasting/auth es necesario para PrivateChannel (Echo/Reverb).
    |
    */
    'paths' => ['api/*', 'auth/*', 'broadcasting/auth'],

    'allowed_methods' => ['*'],

    /*
    |--------------------------------------------------------------------------
    | Allowed Origins
    |--------------------------------------------------------------------------
    | IMPORTANTE: No uses '*' si supports_credentials = true
    */
    'allowed_origins' => ['http://localhost:3000'],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    /*
    |--------------------------------------------------------------------------
    | Supports Credentials
    |--------------------------------------------------------------------------
    | Necesario para que el browser mande cookies (access_token, refresh_token, etc.)
    */
    'supports_credentials' => true,
];
