<?php

return [
    'custom' => [
        'tenant_id' => [
            'required' => 'El campo :attribute es obligatorio.',
            'uuid' => 'El campo :attribute debe ser un UUID válido.',
            'exists' => 'El :attribute seleccionado no es válido.',
        ],
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
        'capacity' => [
            'integer' => 'El campo :attribute debe ser numérico.',
            'min' => 'El campo :attribute debe ser al menos :min.',
        ],
        'location' => [
            'string' => 'El campo :attribute debe ser una cadena de texto.',
            'max' => 'El campo :attribute no debe ser mayor que :max caracteres.',
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
        'tenant_id' => 'tenant',
        'code' => 'código',
        'name' => 'nombre',
        'capacity' => 'capacidad',
        'location' => 'ubicación',
        'description' => 'descripción',
        'is_active' => 'estado activo',
    ],
];
