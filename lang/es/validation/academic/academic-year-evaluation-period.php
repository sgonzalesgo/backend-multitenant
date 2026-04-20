<?php

return [

    'attributes' => [
        'academic_year_id' => 'año académico',
        'evaluation_period_id' => 'período de evaluación',
        'order' => 'orden',
        'start_date' => 'fecha de inicio',
        'end_date' => 'fecha de fin',
        'is_active' => 'estado',
    ],

    'custom' => [
        'academic_year_id' => [
            'required' => 'El :attribute es obligatorio.',
            'uuid' => 'El :attribute debe ser un UUID válido.',
            'exists' => 'El :attribute seleccionado no es válido.',
        ],
        'evaluation_period_id' => [
            'required' => 'El :attribute es obligatorio.',
            'uuid' => 'El :attribute debe ser un UUID válido.',
            'exists' => 'El :attribute seleccionado no es válido.',
            'unique' => 'El :attribute seleccionado ya fue asignado a este año académico.',
        ],
        'order' => [
            'required' => 'El :attribute es obligatorio.',
            'integer' => 'El :attribute debe ser un número.',
            'min' => 'El :attribute debe ser al menos :min.',
            'unique' => 'El :attribute ya está en uso para este año académico.',
        ],
        'start_date' => [
            'required' => 'La :attribute es obligatoria.',
            'date' => 'La :attribute debe ser una fecha válida.',
        ],
        'end_date' => [
            'required' => 'La :attribute es obligatoria.',
            'date' => 'La :attribute debe ser una fecha válida.',
            'after' => 'La :attribute debe ser una fecha posterior a :date.',
        ],
        'is_active' => [
            'boolean' => 'El :attribute debe ser verdadero o falso.',
        ],
    ],

    'messages' => [
        'date_overlap' => 'El rango de fechas seleccionado se superpone con otro período de evaluación en este año académico.',
        'duplicate_evaluation_period' => 'El período de evaluación seleccionado está duplicado en la solicitud.',
        'duplicate_order' => 'El orden está duplicado en la solicitud.',
    ],

];
