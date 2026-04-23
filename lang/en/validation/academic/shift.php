<?php


return [
    'custom' => [
        'tenant_id' => [
            'required' => 'The :attribute field is required.',
            'uuid' => 'The :attribute field must be a valid UUID.',
            'exists' => 'The selected :attribute is invalid.',
        ],
        'code' => [
            'required' => 'The :attribute field is required.',
            'string' => 'The :attribute field must be a string.',
            'max' => 'The :attribute field must not be greater than :max characters.',
            'unique' => 'The :attribute is already in use for this school.',
        ],
        'name' => [
            'required' => 'The :attribute field is required.',
            'string' => 'The :attribute field must be a string.',
            'max' => 'The :attribute field must not be greater than :max characters.',
            'unique' => 'The :attribute is already in use for this school.',
        ],
        'description' => [
            'string' => 'The :attribute field must be a string.',
            'max' => 'The :attribute field must not be greater than :max characters.',
        ],
        'is_active' => [
            'boolean' => 'The :attribute field must be true or false.',
        ],
    ],

    'attributes' => [
        'tenant_id' => 'tenant',
        'code' => 'code',
        'name' => 'name',
        'description' => 'description',
        'is_active' => 'active status',
    ],
];
