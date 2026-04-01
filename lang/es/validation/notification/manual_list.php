<?php


return [
    'custom' => [
        'per_page.integer' => 'La paginación debe ser un número entero.',
        'per_page.min' => 'La paginación debe ser al menos :min.',
        'per_page.max' => 'La paginación no puede ser mayor a :max.',
        'q.string' => 'La búsqueda debe ser texto.',
        'q.max' => 'La búsqueda no puede superar :max caracteres.',
        'archived.string' => 'El filtro archived debe ser texto.',
        'archived.in' => 'El filtro archived debe ser without, only o with.',
    ],
    'attributes' => [
        'per_page' => 'paginación',
        'q' => 'búsqueda',
        'archived' => 'filtro de archivado',
    ],
];
