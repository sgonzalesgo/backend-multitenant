<?php

return [

    'custom' => [
        'user_id.required'        => 'The user identifier is required.',
        'user_id.exists'          => 'The selected user is invalid.',
        'permissions.required'    => 'You must provide a permissions array.',
        'permissions.array'       => 'The permissions field must be an array.',
        'permissions.*.exists'    => 'One or more selected permissions are invalid.',
    ],

    'attributes' => [
        'user_id'     => 'user',
        'permissions' => 'permissions',
        'permissions.*' => 'permission',
    ],

];
