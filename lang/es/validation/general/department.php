<?php

return [
    'custom' => [
        'name' => [
            'required' => 'El campo :attribute es obligatorio.',
            'string' => 'El campo :attribute debe ser una cadena de texto.',
            'max' => 'El campo :attribute no debe ser mayor que :max caracteres.',
        ],

        'code' => [
            'required' => 'El campo :attribute es obligatorio.',
            'string' => 'El campo :attribute debe ser una cadena de texto.',
            'max' => 'El campo :attribute no debe ser mayor que :max caracteres.',
        ],

        'person_id' => [
            'uuid' => 'El campo :attribute debe ser un UUID válido.',
            'exists' => 'La :attribute seleccionada no es válida.',
        ],

        'status' => [
            'string' => 'El campo :attribute debe ser una cadena de texto.',
            'max' => 'El campo :attribute no debe ser mayor que :max caracteres.',
        ],
    ],

    'attributes' => [
        'name' => 'nombre',
        'code' => 'código',
        'person_id' => 'responsable',
        'status' => 'estado',
    ],
];
