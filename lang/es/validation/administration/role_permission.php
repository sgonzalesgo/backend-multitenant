<?php

return [
    'attributes' => [
        'permissions'   => 'permisos',
        'permissions.*' => 'permiso',
    ],
    'custom' => [
        'permissions.required' => 'Los :attribute son obligatorios.',
        'permissions.array'    => 'Los :attribute deben ser un arreglo.',
        'permissions.*.exists' => 'Alguno de los :attribute no existe.',
    ],
];
