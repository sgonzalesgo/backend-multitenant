<?php

return [
    'custom' => [

        // Instructor
        'department_id' => [
            'uuid' => 'El campo :attribute debe ser un UUID válido.',
            'exists' => 'El :attribute seleccionado no es válido.',
        ],
        'academic_title' => [
            'string' => 'El campo :attribute debe ser una cadena de texto.',
            'max' => 'El campo :attribute no debe ser mayor que :max caracteres.',
        ],
        'academic_level' => [
            'string' => 'El campo :attribute debe ser una cadena de texto.',
            'max' => 'El campo :attribute no debe ser mayor que :max caracteres.',
        ],

    ],

    'attributes' => [

        // Instructor
        'department_id' => 'departamento',
        'academic_title' => 'título académico',
        'academic_level' => 'nivel académico',

    ],
];
