<?php

return [
    'attributes' => [
        'user_id'   => 'user',
        'tenant_id' => 'tenant',
        'roles'     => 'roles',
        'roles.*'   => 'role',
    ],
    'custom' => [
        'user_id.required'   => 'The :attribute field is required.',
        'user_id.uuid'       => 'The :attribute format is invalid.',
        'user_id.exists'     => 'The specified :attribute does not exist.',
        'tenant_id.required' => 'The :attribute field is required.',
        'tenant_id.exists'   => 'The specified :attribute does not exist.',
        'roles.required'     => 'The :attribute field is required.',
        'roles.array'        => 'The :attribute must be an array.',
        'roles.*.exists'     => 'One or more :attribute do not exist.',
        'roles.*.tenant_mismatch' => 'One or more roles do not belong to the specified tenant.',
    ],
];
