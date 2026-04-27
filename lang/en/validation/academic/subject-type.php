<?php


return [
    'custom' => [
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
        'is_active' => [
            'boolean' => 'The :attribute field must be true or false.',
        ],
    ],

    'attributes' => [
        'code' => 'code',
        'name' => 'name',
        'description' => 'description',
        'is_active' => 'active status',
    ],
];
