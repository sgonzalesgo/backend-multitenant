<?php

return [
    'attributes' => [
        'name'       => 'role name',
        'guard_name' => 'guard',
        'tenant_id'  => 'tenant',
        'id'         => 'identifier',
    ],
    'custom' => [
        'name.string'       => 'The :attribute must be a string.',
        'name.max'          => 'The :attribute may not be greater than :max characters.',
        'name.required'     => 'The :attribute field is required.',
        'name.unique'       => 'A role with this name already exists for the specified tenant.',
        'tenant_id.required'=> 'The :attribute field is required.',
        'tenant_id.exists'  => 'The specified :attribute does not exist.',
        'guard_name.string' => 'The :attribute must be a string.',
        'guard_name.max'    => 'The :attribute may not be greater than :max characters.',
        'description.string'=> 'The :attribute must be a string.',
        'description.max'   => 'The :attribute may not be greater than :max characters.',
    ],
];
