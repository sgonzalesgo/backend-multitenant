<?php

return [
    'custom' => [
        'email.required' => 'El correo electrónico es obligatorio.',
        'email.email' => 'Debes ingresar un correo electrónico válido.',
        'email.max' => 'El correo electrónico no puede tener más de :max caracteres.',

        'code.required' => 'El código es obligatorio.',
        'code.digits' => 'El código debe tener exactamente 6 dígitos.',

        'password.required' => 'La contraseña es obligatoria.',
        'password.min' => 'La contraseña debe tener al menos :min caracteres.',
        'password.confirmed' => 'La confirmación de la contraseña no coincide.',
    ],
    'attributes' => [
        'email' => 'correo electrónico',
        'code' => 'código',
        'password' => 'contraseña',
    ],
];
