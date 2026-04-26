<?php


return [
    'custom' => [
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
        'sort_order' => [
            'required' => 'The :attribute field is required.',
            'integer' => 'The :attribute must be a number.',
            'min' => 'The :attribute must be at least :min.',
            'unique' => 'The :attribute is already in use for this school.',
        ],
        'start_number' => [
            'required' => 'The :attribute field is required.',
            'integer' => 'The :attribute must be a number.',
            'min' => 'The :attribute must be at least :min.',
        ],
        'end_number' => [
            'required' => 'The :attribute field is required.',
            'integer' => 'The :attribute must be a number.',
            'min' => 'The :attribute must be at least :min.',
            'gte' => 'The :attribute must be greater than or equal to start number.',
        ],
        'has_specialty' => [
            'boolean' => 'The :attribute field must be true or false.',
        ],
        'next_educational_level_id' => [
            'uuid' => 'The :attribute must be a valid UUID.',
            'exists' => 'The selected :attribute is invalid.',
            'different' => 'The :attribute must be different from the current level.',
        ],
        'description' => [
            'string' => 'The :attribute field must be a string.',
            'max' => 'The :attribute must not be greater than :max characters.',
        ],
        'is_active' => [
            'boolean' => 'The :attribute field must be true or false.',
        ],
    ],

    'attributes' => [
        'code' => 'code',
        'name' => 'name',
        'sort_order' => 'order',
        'start_number' => 'start number',
        'end_number' => 'end number',
        'has_specialty' => 'has specialty',
        'next_educational_level_id' => 'next educational level',
        'description' => 'description',
        'is_active' => 'active status',
    ],
];
