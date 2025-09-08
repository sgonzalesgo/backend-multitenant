<?php

return [
    'attributes' => [
        'name' => 'nombre del permiso',
        'guard_name' => 'guard',
        'id' => 'identificador',
    ],
    'custom' => [
        'name.required' => 'El :attribute es obligatorio.',
        'name.string' => 'El :attribute debe ser una cadena de texto.',
        'name.max' => 'El :attribute no puede tener más de :max caracteres.',
        'name.unique' => 'Ya existe un permiso con este nombre para el guard especificado.',
        'guard_name.string' => 'El :attribute debe ser una cadena de texto.',
        'guard_name.max' => 'El :attribute no puede tener más de :max caracteres.',
    ],
];
