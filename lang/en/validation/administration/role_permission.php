<?php

return [
    'attributes' => [
        'permissions'   => 'permissions',
        'permissions.*' => 'permission',
    ],
    'custom' => [
        'permissions.required' => 'The :attribute field is required.',
        'permissions.array'    => 'The :attribute must be an array.',
        'permissions.*.exists' => 'One or more :attribute do not exist.',
    ],
];
