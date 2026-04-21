<?php

return [

    'attributes' => [
        'academic_year_id' => 'año académico',
        'code' => 'código',
        'name' => 'nombre',
        'description' => 'descripción',
        'default_order' => 'orden por defecto',
        'start_date' => 'fecha de inicio',
        'end_date' => 'fecha de fin',
        'is_active' => 'estado',
    ],

    'custom' => [
        'academic_year_id' => [
            'required' => 'El :attribute es obligatorio.',
            'uuid' => 'El :attribute debe ser un UUID válido.',
            'exists' => 'El :attribute seleccionado no existe.',
        ],
        'code' => [
            'required' => 'El :attribute es obligatorio.',
            'string' => 'El :attribute debe ser un texto.',
            'max' => 'El :attribute no puede tener más de :max caracteres.',
            'unique' => 'El :attribute ya está en uso para este año académico.',
        ],
        'name' => [
            'required' => 'El :attribute es obligatorio.',
            'string' => 'El :attribute debe ser un texto.',
            'max' => 'El :attribute no puede tener más de :max caracteres.',
            'unique' => 'El :attribute ya está en uso para este año académico.',
        ],
        'description' => [
            'string' => 'La :attribute debe ser un texto.',
            'max' => 'La :attribute no puede tener más de :max caracteres.',
        ],
        'default_order' => [
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
            'after_or_equal' => 'La :attribute debe ser una fecha posterior o igual a la fecha de inicio.',
        ],
        'is_active' => [
            'boolean' => 'El :attribute debe ser verdadero o falso.',
        ],
    ],

];
