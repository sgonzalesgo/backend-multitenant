<?php


return [
    'custom' => [
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
        'educational_level_id' => [
            'uuid' => 'El campo :attribute debe ser un UUID válido.',
            'exists' => 'El :attribute seleccionado no es válido.',
        ],
        'course_id' => [
            'uuid' => 'El campo :attribute debe ser un UUID válido.',
            'exists' => 'El :attribute seleccionado no es válido.',
        ],
        'evaluation_period_id' => [
            'uuid' => 'El campo :attribute debe ser un UUID válido.',
            'exists' => 'El :attribute seleccionado no es válido.',
        ],
        'is_active' => [
            'boolean' => 'El campo :attribute debe ser verdadero o falso.',
        ],
        'skill_definition_ids' => [
            'required' => 'Debe seleccionar al menos una :attribute.',
            'array' => 'El campo :attribute debe ser una lista.',
            'min' => 'Debe seleccionar al menos una :attribute.',
        ],
        'skill_definition_ids.*' => [
            'required' => 'Cada destreza seleccionada es obligatoria.',
            'uuid' => 'Cada destreza seleccionada debe ser un UUID válido.',
            'exists' => 'Una o más destrezas seleccionadas no son válidas.',
        ],
    ],

    'attributes' => [
        'name' => 'nombre',
        'description' => 'descripción',
        'educational_level_id' => 'nivel educativo',
        'course_id' => 'curso',
        'evaluation_period_id' => 'período de evaluación',
        'is_active' => 'estado activo',
        'skill_definition_ids' => 'destreza',
        'skill_definition_ids.*' => 'destreza',
    ],
];
