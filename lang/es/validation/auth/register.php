<?php


return [
    'attributes' => [
        'name' => 'nombre',
        'email' => 'correo electrónico',
        'password' => 'contraseña',
        'locale' => 'idioma',
    ],
    'custom' => [
        'email.unique' => 'Ya existe una cuenta con este correo.',
        'password.confirmed' => 'La contraseña y su confirmación no coinciden.',
    ],
];
