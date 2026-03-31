<?php

return [
    'attributes' => [
        'name' => 'nombre',
        'email' => 'correo electrónico',
        'password' => 'contraseña',
        'password_confirmation' => 'confirmación de contraseña',
        'locale' => 'idioma',
        'avatar' => 'avatar',
    ],

    'custom' => [
        'name.required' => 'El :attribute es obligatorio.',
        'email.required' => 'El :attribute es obligatorio.',
        'email.email' => 'El :attribute debe ser un correo válido.',
        'email.unique' => 'Ya existe una cuenta con este correo.',

        'password.required' => 'La :attribute es obligatoria.',
        'password.min' => 'La :attribute debe tener al menos :min caracteres.',
        'password.confirmed' => 'La contraseña y su confirmación no coinciden.',

        'avatar.image' => 'El archivo debe ser una imagen válida.',
        'avatar.max' => 'La imagen no debe superar los :max KB.',
    ],
];
