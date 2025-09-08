<?php

return [
    'attributes' => [
        'user_id'   => 'usuario',
        'tenant_id' => 'empresa',
        'roles'     => 'roles',
        'roles.*'   => 'rol',
    ],
    'custom' => [
        'user_id.required'   => 'El :attribute es obligatorio.',
        'user_id.uuid'       => 'El :attribute no tiene un formato válido.',
        'user_id.exists'     => 'El :attribute no existe.',
        'tenant_id.required' => 'La :attribute es obligatoria.',
        'tenant_id.exists'   => 'La :attribute no existe.',
        'roles.required'     => 'Los :attribute son obligatorios.',
        'roles.array'        => 'Los :attribute deben ser un arreglo.',
        'roles.*.exists'     => 'Alguno de los :attribute no existe.',
        'roles.*.tenant_mismatch' => 'Alguno de los roles no pertenece al tenant especificado.',
    ],
];
