<?php


return [
    'custom' => [
        'tenant_id' => [
            'required' => 'The :attribute field is required.',
            'uuid' => 'The :attribute must be a valid UUID.',
            'exists' => 'The selected :attribute is invalid.',
        ],
        'code' => [
            'required' => 'The :attribute field is required.',
            'string' => 'The :attribute must be a string.',
            'max' => 'The :attribute may not be greater than :max characters.',
            'unique' => 'The :attribute has already been taken for this school.',
        ],
        'name' => [
            'required' => 'The :attribute field is required.',
            'string' => 'The :attribute must be a string.',
            'max' => 'The :attribute may not be greater than :max characters.',
            'unique' => 'The :attribute has already been taken for this school.',
        ],
        'description' => [
            'string' => 'The :attribute must be a string.',
            'max' => 'The :attribute may not be greater than :max characters.',
        ],
        'start_date' => [
            'required' => 'The :attribute field is required.',
            'date' => 'The :attribute is not a valid date.',
        ],
        'end_date' => [
            'required' => 'The :attribute field is required.',
            'date' => 'The :attribute is not a valid date.',
            'after' => 'The :attribute must be a date after :date.',
        ],
        'is_active' => [
            'boolean' => 'The :attribute field must be true or false.',
        ],
        'is_current' => [
            'boolean' => 'The :attribute field must be true or false.',
        ],
    ],

    'attributes' => [
        'tenant_id' => 'tenant',
        'code' => 'code',
        'name' => 'name',
        'description' => 'description',
        'start_date' => 'start date',
        'end_date' => 'end date',
        'is_active' => 'active status',
        'is_current' => 'current status',
    ],
];
