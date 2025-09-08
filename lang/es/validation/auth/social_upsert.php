<?php
return [
    'attributes' => [
        'provider'     => 'proveedor',
        'access_token' => 'token de acceso',
        'email'        => 'correo electrónico',
        'name'         => 'nombre',
        'avatar'       => 'avatar',
        'locale'       => 'idioma',
    ],
    'custom' => [
        'provider.in'      => 'El proveedor debe ser google o facebook.',
        'access_token.required' => 'El token de acceso es obligatorio.',
        'access_token.min' => 'El token de acceso no es válido.',
        'email.email'      => 'El correo no es válido.',
        'avatar.url'       => 'El avatar debe ser una URL válida.',
    ],
];
