<?php

return [
    'custom' => [
        'title' => [
            'required' => 'El campo :attribute es obligatorio.',
            'string' => 'El campo :attribute debe ser una cadena de texto.',
            'max' => 'El campo :attribute no debe ser mayor que :max caracteres.',
        ],

        'description' => [
            'required' => 'El campo :attribute es obligatorio.',
            'string' => 'El campo :attribute debe ser una cadena de texto.',
        ],

        'category' => [
            'string' => 'El campo :attribute debe ser una cadena de texto.',
            'enum' => 'La :attribute seleccionada no es válida.',
        ],

        'priority' => [
            'string' => 'El campo :attribute debe ser una cadena de texto.',
            'enum' => 'La :attribute seleccionada no es válida.',
        ],

        'status' => [
            'required' => 'El campo :attribute es obligatorio.',
            'string' => 'El campo :attribute debe ser una cadena de texto.',
            'enum' => 'El :attribute seleccionado no es válido.',
        ],

        'assigned_to_id' => [
            'required' => 'El campo :attribute es obligatorio.',
            'uuid' => 'El campo :attribute debe ser un UUID válido.',
            'exists' => 'El :attribute seleccionado no es válido.',
        ],

        'comment' => [
            'required' => 'El campo :attribute es obligatorio.',
            'string' => 'El campo :attribute debe ser una cadena de texto.',
        ],

        'is_internal' => [
            'boolean' => 'El campo :attribute debe ser verdadero o falso.',
        ],

        'attachments' => [
            'array' => 'El campo :attribute debe ser un arreglo.',
        ],

        'attachments.*' => [
            'file' => 'Cada :attribute debe ser un archivo válido.',
            'max' => 'Cada :attribute no debe ser mayor que :max kilobytes.',
        ],
    ],

    'attributes' => [
        'title' => 'título',
        'description' => 'descripción',
        'category' => 'categoría',
        'priority' => 'prioridad',
        'status' => 'estado',
        'assigned_to_id' => 'usuario asignado',
        'comment' => 'comentario',
        'is_internal' => 'comentario interno',
        'attachments' => 'adjuntos',
        'attachments.*' => 'adjunto',
    ],
];
