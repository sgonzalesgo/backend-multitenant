<?php

return [
    'custom' => [
        'subject_type_id' => [
            'uuid' => 'El campo :attribute debe ser un UUID válido.',
            'exists' => 'El :attribute seleccionado no es válido.',
        ],
        'evaluation_type_id' => [
            'uuid' => 'El campo :attribute debe ser un UUID válido.',
            'exists' => 'El :attribute seleccionado no es válido.',
        ],
        'code' => [
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
        'is_average' => [
            'boolean' => 'El campo :attribute debe ser verdadero o falso.',
        ],
        'is_behavior' => [
            'boolean' => 'El campo :attribute debe ser verdadero o falso.',
        ],
        'is_active' => [
            'boolean' => 'El campo :attribute debe ser verdadero o falso.',
        ],
    ],

    'attributes' => [
        'subject_type_id' => 'tipo de asignatura',
        'evaluation_type_id' => 'tipo de evaluación',
        'code' => 'código',
        'name' => 'nombre',
        'description' => 'descripción',
        'is_average' => 'estado promediable',
        'is_behavior' => 'estado de comportamiento',
        'is_active' => 'estado activo',
    ],
];
