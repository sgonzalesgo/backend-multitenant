<?php

return [

    'custom' => [
        'user_id.required'        => 'Debe enviar el identificador del usuario.',
        'user_id.exists'          => 'El usuario seleccionado no es válido.',
        'permissions.required'    => 'Debe enviar un arreglo de permisos.',
        'permissions.array'       => 'El campo permisos debe ser un arreglo.',
        'permissions.*.exists'    => 'Uno o más permisos seleccionados no son válidos.',
    ],

    'attributes' => [
        'user_id'     => 'usuario',
        'permissions' => 'permisos',
        'permissions.*' => 'permiso',
    ],

];
