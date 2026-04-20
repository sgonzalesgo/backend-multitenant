<?php

return [

    'attributes' => [
        'code' => 'código',
        'name' => 'nombre',
        'description' => 'descripción',
        'default_order' => 'orden por defecto',
        'is_active' => 'estado',
    ],

    'custom' => [
        'code' => [
            'required' => 'El :attribute es obligatorio.',
            'string' => 'El :attribute debe ser un texto.',
            'max' => 'El :attribute no puede tener más de :max caracteres.',
            'unique' => 'El :attribute ya está en uso.',
        ],
        'name' => [
            'required' => 'El :attribute es obligatorio.',
            'string' => 'El :attribute debe ser un texto.',
            'max' => 'El :attribute no puede tener más de :max caracteres.',
            'unique' => 'El :attribute ya está en uso.',
        ],
        'description' => [
            'string' => 'La :attribute debe ser un texto.',
            'max' => 'La :attribute no puede tener más de :max caracteres.',
        ],
        'default_order' => [
            'required' => 'El :attribute es obligatorio.',
            'integer' => 'El :attribute debe ser un número.',
            'min' => 'El :attribute debe ser al menos :min.',
        ],
        'is_active' => [
            'boolean' => 'El :attribute debe ser verdadero o falso.',
        ],
    ],

];
