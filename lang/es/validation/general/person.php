<?php

return [
    'custom' => [
        'full_name' => [
            'required' => 'El campo :attribute es obligatorio.',
            'string' => 'El campo :attribute debe ser una cadena de texto.',
            'max' => 'El campo :attribute no debe ser mayor que :max caracteres.',
        ],
        'photo' => [
            'file' => 'El campo :attribute debe ser un archivo válido.',
            'image' => 'El campo :attribute debe ser una imagen.',
            'mimes' => 'El campo :attribute debe ser un archivo de tipo: :values.',
            'max' => 'El campo :attribute no debe ser mayor que :max kilobytes.',
        ],
        'email' => [
            'email' => 'El campo :attribute debe ser un correo electrónico válido.',
            'max' => 'El campo :attribute no debe ser mayor que :max caracteres.',
        ],
        'phone' => [
            'string' => 'El campo :attribute debe ser una cadena de texto.',
            'max' => 'El campo :attribute no debe ser mayor que :max caracteres.',
        ],
        'address' => [
            'string' => 'El campo :attribute debe ser una cadena de texto.',
            'max' => 'El campo :attribute no debe ser mayor que :max caracteres.',
        ],
        'city' => [
            'string' => 'El campo :attribute debe ser una cadena de texto.',
            'max' => 'El campo :attribute no debe ser mayor que :max caracteres.',
        ],
        'state' => [
            'string' => 'El campo :attribute debe ser una cadena de texto.',
            'max' => 'El campo :attribute no debe ser mayor que :max caracteres.',
        ],
        'country' => [
            'string' => 'El campo :attribute debe ser una cadena de texto.',
            'max' => 'El campo :attribute no debe ser mayor que :max caracteres.',
        ],
        'zip' => [
            'string' => 'El campo :attribute debe ser una cadena de texto.',
            'max' => 'El campo :attribute no debe ser mayor que :max caracteres.',
        ],
        'legal_id' => [
            'required' => 'El campo :attribute es obligatorio.',
            'string' => 'El campo :attribute debe ser una cadena de texto.',
            'max' => 'El campo :attribute no debe ser mayor que :max caracteres.',
        ],
        'legal_id_type' => [
            'required' => 'El campo :attribute es obligatorio.',
            'string' => 'El campo :attribute debe ser una cadena de texto.',
            'max' => 'El campo :attribute no debe ser mayor que :max caracteres.',
            'unique' => 'Ya existe una persona con este tipo de identificación e identificación.',
        ],
        'birthday' => [
            'date' => 'El campo :attribute debe ser una fecha válida.',
        ],
        'gender' => [
            'string' => 'El campo :attribute debe ser una cadena de texto.',
            'max' => 'El campo :attribute no debe ser mayor que :max caracteres.',
        ],
        'marital_status' => [
            'string' => 'El campo :attribute debe ser una cadena de texto.',
            'max' => 'El campo :attribute no debe ser mayor que :max caracteres.',
        ],
        'blood_group' => [
            'string' => 'El campo :attribute debe ser una cadena de texto.',
            'max' => 'El campo :attribute no debe ser mayor que :max caracteres.',
        ],
        'nationality' => [
            'string' => 'El campo :attribute debe ser una cadena de texto.',
            'max' => 'El campo :attribute no debe ser mayor que :max caracteres.',
        ],
        'status' => [
            'string' => 'El campo :attribute debe ser una cadena de texto.',
            'max' => 'El campo :attribute no debe ser mayor que :max caracteres.',
        ],
        'deceased_at' => [
            'date' => 'El campo :attribute debe ser una fecha válida.',
        ],
        'status_changed_at' => [
            'date' => 'El campo :attribute debe ser una fecha válida.',
        ],

        'has_user' => [
            'boolean' => 'El campo :attribute debe ser verdadero o falso.',
        ],
        'user_name' => [
            'required_if' => 'El campo :attribute es obligatorio cuando se debe crear o actualizar un usuario.',
            'string' => 'El campo :attribute debe ser una cadena de texto.',
            'max' => 'El campo :attribute no debe ser mayor que :max caracteres.',
        ],
        'user_email' => [
            'required_if' => 'El campo :attribute es obligatorio cuando se debe crear o actualizar un usuario.',
            'email' => 'El campo :attribute debe ser un correo electrónico válido.',
            'max' => 'El campo :attribute no debe ser mayor que :max caracteres.',
            'unique' => 'El campo :attribute ya está en uso.',
        ],
        'user_password' => [
            'required_if' => 'El campo :attribute es obligatorio cuando se debe crear un usuario.',
            'confirmed' => 'La confirmación de :attribute no coincide.',
        ],
        'user_status' => [
            'string' => 'El campo :attribute debe ser una cadena de texto.',
            'max' => 'El campo :attribute no debe ser mayor que :max caracteres.',
        ],
    ],

    'attributes' => [
        'full_name' => 'nombre completo',
        'photo' => 'foto',
        'email' => 'correo electrónico',
        'phone' => 'teléfono',
        'address' => 'dirección',
        'city' => 'ciudad',
        'state' => 'provincia',
        'country' => 'país',
        'zip' => 'código postal',
        'legal_id' => 'identificación',
        'legal_id_type' => 'tipo de identificación',
        'birthday' => 'fecha de nacimiento',
        'gender' => 'género',
        'marital_status' => 'estado civil',
        'blood_group' => 'tipo de sangre',
        'nationality' => 'nacionalidad',
        'status' => 'estado',
        'deceased_at' => 'fecha de fallecimiento',
        'status_changed_at' => 'fecha de cambio de estado',

        'has_user' => 'crear usuario',
        'user_name' => 'nombre del usuario',
        'user_email' => 'correo del usuario',
        'user_password' => 'contraseña del usuario',
        'user_password_confirmation' => 'confirmación de contraseña del usuario',
        'user_status' => 'estado del usuario',
    ],
];
