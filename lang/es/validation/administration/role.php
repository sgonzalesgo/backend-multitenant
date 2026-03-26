<?php

return [
    'attributes' => [
        'name'       => 'nombre del rol',
        'guard_name' => 'guard',
        'id'         => 'identificador',
    ],
    'custom' => [
        'name.string' => 'El :attribute debe ser una cadena de texto.',
        'name.max' => 'El :attribute no puede tener más de :max caracteres.',
        'name.required'     => 'El :attribute es obligatorio.',
        'name.unique'       => 'Ya existe un rol con ese nombre en la empresa indicada.',
        'guard_name.string' => 'El :attribute debe ser una cadena de texto.',
        'guard_name.max' => 'El :attribute no puede tener más de :max caracteres.',
        'description.string' => 'La :attribute debe ser una cadena de texto.',
        'description.max' => 'La :attribute no puede tener más de :max caracteres.',
    ],
];
