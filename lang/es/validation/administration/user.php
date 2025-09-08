<?php

return [
    'attributes' => [
        'name' => 'nombre',
        'email' => 'correo electrónico',
        'password' => 'contraseña',
        'password_confirmation' => 'confirmación de contraseña',
        'locale' => 'idioma',
        'avatar' => 'avatar',
        'is_active' => 'activo',
    ],
    'custom' => [
        'name.required' => 'El :attribute es obligatorio.',
        'email.required' => 'El :attribute es obligatorio.',
        'email.email' => 'El :attribute no es válido.',
        'email.unique' => 'Ya existe un usuario con este :attribute.',
        'password.required' => 'La :attribute es obligatoria.',
        'password.min' => 'La :attribute debe tener al menos :min caracteres.',
        'password.confirmed' => 'La :attribute no coincide con la confirmación.',
        'avatar.url' => 'El :attribute debe ser una URL válida.',
    ],
];
