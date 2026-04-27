<?php

return [
    'custom' => [
        'subject_type_id' => [
            'uuid' => 'The :attribute must be a valid UUID.',
            'exists' => 'The selected :attribute is invalid.',
        ],
        'evaluation_type_id' => [
            'uuid' => 'The :attribute must be a valid UUID.',
            'exists' => 'The selected :attribute is invalid.',
        ],
        'code' => [
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
        'is_average' => [
            'boolean' => 'The :attribute field must be true or false.',
        ],
        'is_behavior' => [
            'boolean' => 'The :attribute field must be true or false.',
        ],
        'is_active' => [
            'boolean' => 'The :attribute field must be true or false.',
        ],
    ],

    'attributes' => [
        'subject_type_id' => 'subject type',
        'evaluation_type_id' => 'evaluation type',
        'code' => 'code',
        'name' => 'name',
        'description' => 'description',
        'is_average' => 'average status',
        'is_behavior' => 'behavior status',
        'is_active' => 'active status',
    ],
];
