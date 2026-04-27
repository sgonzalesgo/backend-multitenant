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
        'description' => 'descripción',
        'is_active' => 'estado activo',
    ],
];
