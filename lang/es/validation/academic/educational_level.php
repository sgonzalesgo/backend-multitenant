<?php

return [
    'custom' => [
        'code' => [
            'required' => 'El campo :attribute es obligatorio.',
            'string' => 'El campo :attribute debe ser una cadena de texto.',
            'max' => 'El campo :attribute no debe ser mayor que :max caracteres.',
            'unique' => 'El :attribute ya está en uso para este colegio.',
        ],
        'name' => [
            'required' => 'El campo :attribute es obligatorio.',
            'string' => 'El campo :attribute debe ser una cadena de texto.',
            'max' => 'El campo :attribute no debe ser mayor que :max caracteres.',
            'unique' => 'El :attribute ya está en uso para este colegio.',
        ],
        'sort_order' => [
            'required' => 'El campo :attribute es obligatorio.',
            'integer' => 'El campo :attribute debe ser numérico.',
            'min' => 'El campo :attribute debe ser al menos :min.',
            'unique' => 'El :attribute ya está en uso para este colegio.',
        ],
        'start_number' => [
            'required' => 'El campo :attribute es obligatorio.',
            'integer' => 'El campo :attribute debe ser numérico.',
            'min' => 'El campo :attribute debe ser al menos :min.',
        ],
        'end_number' => [
            'required' => 'El campo :attribute es obligatorio.',
            'integer' => 'El campo :attribute debe ser numérico.',
            'min' => 'El campo :attribute debe ser al menos :min.',
            'gte' => 'El campo :attribute debe ser mayor o igual al número inicial.',
        ],
        'has_specialty' => [
            'boolean' => 'El campo :attribute debe ser verdadero o falso.',
        ],
        'next_educational_level_id' => [
            'uuid' => 'El campo :attribute debe ser un UUID válido.',
            'exists' => 'El :attribute seleccionado no es válido.',
            'different' => 'El :attribute debe ser diferente del nivel actual.',
        ],
        'description' => [
            'string' => 'El campo :attribute debe ser una cadena de texto.',
            'max' => 'El campo :attribute no debe ser mayor que :max caracteres.',
        ],
        'is_active' => [
            'boolean' => 'El campo :attribute debe ser verdadero o falso.',
        ],
    ],

    'attributes' => [
        'code' => 'código',
        'name' => 'nombre',
        'sort_order' => 'orden',
        'start_number' => 'número inicial',
        'end_number' => 'número final',
        'has_specialty' => 'tiene especialidad',
        'next_educational_level_id' => 'siguiente nivel educativo',
        'description' => 'descripción',
        'is_active' => 'estado activo',
    ],
];
