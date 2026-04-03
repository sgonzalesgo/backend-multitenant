<?php


return [
    'custom' => [
        'person_id' => [
            'unique_in_tenant' => 'La persona ya está registrada como instructor en el tenant actual.',
        ],
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
        'legal_id' => [
            'required' => 'El campo :attribute es obligatorio.',
            'string' => 'El campo :attribute debe ser una cadena de texto.',
            'max' => 'El campo :attribute no debe ser mayor que :max caracteres.',
        ],
        'legal_id_type' => [
            'required' => 'El campo :attribute es obligatorio.',
            'string' => 'El campo :attribute debe ser una cadena de texto.',
            'max' => 'El campo :attribute no debe ser mayor que :max caracteres.',
        ],
        'birthday' => [
            'date' => 'El campo :attribute debe ser una fecha válida.',
        ],
        'deceased_at' => [
            'date' => 'El campo :attribute debe ser una fecha válida.',
        ],
        'person_status_changed_at' => [
            'date' => 'El campo :attribute debe ser una fecha válida.',
        ],
        'status_changed_at' => [
            'date' => 'El campo :attribute debe ser una fecha válida.',
        ],
        'code' => [
            'string' => 'El campo :attribute debe ser una cadena de texto.',
            'max' => 'El campo :attribute no debe ser mayor que :max caracteres.',
        ],
        'academic_title' => [
            'string' => 'El campo :attribute debe ser una cadena de texto.',
            'max' => 'El campo :attribute no debe ser mayor que :max caracteres.',
        ],
        'academic_level' => [
            'string' => 'El campo :attribute debe ser una cadena de texto.',
            'max' => 'El campo :attribute no debe ser mayor que :max caracteres.',
        ],
        'specialty' => [
            'string' => 'El campo :attribute debe ser una cadena de texto.',
            'max' => 'El campo :attribute no debe ser mayor que :max caracteres.',
        ],
        'status' => [
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
        'person_status' => 'estado de la persona',
        'deceased_at' => 'fecha de fallecimiento',
        'person_status_changed_at' => 'fecha de cambio de estado de la persona',
        'code' => 'código',
        'academic_title' => 'título académico',
        'academic_level' => 'nivel académico',
        'specialty' => 'especialidad',
        'status' => 'estado',
        'status_changed_at' => 'fecha de cambio de estado',
    ],
];
