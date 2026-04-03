<?php

return [
    'custom' => [

        'person_id' => [
            'uuid' => 'El campo :attribute debe ser un UUID válido.',
            'exists' => 'La :attribute seleccionada no es válida.',
            'unique' => 'Esta :attribute ya está asociada a otro usuario.',
        ],

        'name' => [
            'required' => 'El campo :attribute es obligatorio.',
            'string' => 'El campo :attribute debe ser una cadena de texto.',
            'max' => 'El campo :attribute no debe ser mayor que :max caracteres.',
        ],

        'email' => [
            'required' => 'El campo :attribute es obligatorio.',
            'email' => 'El campo :attribute debe ser un correo electrónico válido.',
            'max' => 'El campo :attribute no debe ser mayor que :max caracteres.',
            'unique' => 'El campo :attribute ya está en uso.',
        ],

        'password' => [
            'required' => 'El campo :attribute es obligatorio.',
            'string' => 'El campo :attribute debe ser una cadena de texto.',
            'min' => 'El campo :attribute debe tener al menos :min caracteres.',
            'confirmed' => 'La confirmación de :attribute no coincide.',
        ],

        'status' => [
            'string' => 'El campo :attribute debe ser una cadena de texto.',
            'max' => 'El campo :attribute no debe ser mayor que :max caracteres.',
        ],

        'locale' => [
            'string' => 'El campo :attribute debe ser una cadena de texto.',
            'max' => 'El campo :attribute no debe ser mayor que :max caracteres.',
        ],

        'avatar' => [
            'file' => 'El campo :attribute debe ser un archivo válido.',
            'image' => 'El campo :attribute debe ser una imagen.',
            'max' => 'El campo :attribute no debe ser mayor que :max kilobytes.',
        ],
    ],

    'attributes' => [
        'person_id' => 'persona',
        'name' => 'nombre',
        'email' => 'correo electrónico',
        'password' => 'contraseña',
        'password_confirmation' => 'confirmación de contraseña',
        'status' => 'estado',
        'locale' => 'idioma',
        'avatar' => 'avatar',
    ],
];
