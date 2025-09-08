<?php


return [
    'attributes' => [
        'provider' => 'proveedor',
        'token' => 'token',
    ],
    'custom' => [
        'provider.in' => 'El proveedor debe ser google o facebook.',
        'token.required' => 'El token es obligatorio.',
    ],
];
