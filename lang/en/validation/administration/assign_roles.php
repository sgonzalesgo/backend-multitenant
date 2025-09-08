<?php

return [
    'attributes' => [
        'user_id'   => 'user',
        'tenant_id' => 'tenant',
        'roles'     => 'roles',
        'roles.*'   => 'role',
    ],

    'custom' => [
        'user_id' => [
            'required' => 'The :attribute is required.',
            'uuid'     => 'The :attribute must be a valid UUID.',
            'exists'   => 'The selected :attribute does not exist.',
        ],

        'tenant_id' => [
            'required' => 'The :attribute is required.',
            'exists'   => 'The selected :attribute does not exist.',
        ],

        'roles' => [
            'required' => 'The :attribute field is required.',
            'array'    => 'The :attribute must be an array.',
            '*' => [
                'exists'          => 'The selected :attribute does not exist.',
                'tenant_mismatch' => 'One or more roles do not belong to the specified tenant.',
            ],
        ],
    ],
];
