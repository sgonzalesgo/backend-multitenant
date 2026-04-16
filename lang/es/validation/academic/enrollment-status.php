<?php


return [
    'custom' => [
        'code.required' => 'El código es obligatorio.',
        'code.string' => 'El código debe ser una cadena de texto.',
        'code.max' => 'El código no debe exceder :max caracteres.',
        'code.unique' => 'Ya existe un estado de matrícula con este código.',

        'name.required' => 'El nombre es obligatorio.',
        'name.string' => 'El nombre debe ser una cadena de texto.',
        'name.max' => 'El nombre no debe exceder :max caracteres.',
        'name.unique' => 'Ya existe un estado de matrícula con este nombre.',

        'description.string' => 'La descripción debe ser una cadena de texto.',
        'description.max' => 'La descripción no debe exceder :max caracteres.',

        'is_active.boolean' => 'El campo activo debe ser verdadero o falso.',

        'sort_order.integer' => 'El orden debe ser un número entero.',
        'sort_order.min' => 'El orden no puede ser menor que :min.',
    ],

    'attributes' => [
        'code' => 'código',
        'name' => 'nombre',
        'description' => 'descripción',
        'is_active' => 'activo',
        'sort_order' => 'orden',
    ],
];
