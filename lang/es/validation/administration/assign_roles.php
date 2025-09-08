<?php

return [
    'attributes' => [
        'user_id'   => 'usuario',
        'tenant_id' => 'empresa',
        'roles'     => 'roles',
        'roles.*'   => 'rol',
    ],
    'custom' => [
        'user_id' => [
            'required' => 'El :attribute es obligatorio.',
            'uuid'     => 'El :attribute no tiene un formato válido.',
            'exists'   => 'El :attribute no existe.',
        ],
        'tenant_id' => [
            'required' => 'La :attribute es obligatoria.',
            'exists'   => 'La :attribute no existe.',
        ],
        'roles' => [
            'required' => 'Los :attribute son obligatorios.',
            'array'    => 'Los :attribute deben ser un arreglo.',
            '*' => [
                'exists'          => 'Alguno de los :attribute no existe.',
                'tenant_mismatch' => 'Alguno de los roles no pertenece al tenant especificado.',
            ],
        ],
    ],
];
