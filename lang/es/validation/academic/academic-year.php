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
        'description' => [
            'string' => 'El campo :attribute debe ser una cadena de texto.',
            'max' => 'El campo :attribute no debe ser mayor que :max caracteres.',
        ],
        'start_date' => [
            'required' => 'El campo :attribute es obligatorio.',
            'date' => 'El campo :attribute no es una fecha válida.',
        ],
        'end_date' => [
            'required' => 'El campo :attribute es obligatorio.',
            'date' => 'El campo :attribute no es una fecha válida.',
            'after' => 'El campo :attribute debe ser una fecha posterior a :date.',
        ],
        'is_active' => [
            'boolean' => 'El campo :attribute debe ser verdadero o falso.',
        ],
        'is_current' => [
            'boolean' => 'El campo :attribute debe ser verdadero o falso.',
        ],
    ],

    'attributes' => [
        'tenant_id' => 'tenant',
        'code' => 'código',
        'name' => 'nombre',
        'description' => 'descripción',
        'start_date' => 'fecha de inicio',
        'end_date' => 'fecha de fin',
        'is_active' => 'estado activo',
        'is_current' => 'estado actual',
    ],
];
