<?php

return [

    'custom' => [

        'academic_year_id' => [
            'uuid' => 'El campo :attribute debe ser un UUID válido.',
            'exists' => 'El :attribute seleccionado no es válido.',
        ],

        'date' => [
            'required' => 'El campo :attribute es obligatorio.',
            'date' => 'El campo :attribute debe ser una fecha válida.',
        ],

        'name' => [
            'required' => 'El campo :attribute es obligatorio.',
            'string' => 'El campo :attribute debe ser una cadena de texto.',
            'max' => 'El campo :attribute no debe ser mayor que :max caracteres.',
        ],

        'type' => [
            'required' => 'El campo :attribute es obligatorio.',
            'string' => 'El campo :attribute debe ser una cadena de texto.',
            'in' => 'El :attribute seleccionado no es válido.',
        ],

        'affects_attendance' => [
            'boolean' => 'El campo :attribute debe ser verdadero o falso.',
        ],

        'affects_calendar' => [
            'boolean' => 'El campo :attribute debe ser verdadero o falso.',
        ],

        'is_active' => [
            'boolean' => 'El campo :attribute debe ser verdadero o falso.',
        ],

        'observation' => [
            'string' => 'El campo :attribute debe ser una cadena de texto.',
            'max' => 'El campo :attribute no debe ser mayor que :max caracteres.',
        ],
    ],

    'attributes' => [
        'academic_year_id' => 'año académico',
        'date' => 'fecha',
        'name' => 'nombre',
        'type' => 'tipo',
        'affects_attendance' => 'afecta asistencia',
        'affects_calendar' => 'afecta calendario',
        'is_active' => 'activo',
        'observation' => 'observación',
    ],
];
