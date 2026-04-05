<?php

return [
    'messages' => [
        'listed' => 'Positions listed successfully.',
        'retrieved' => 'Position retrieved successfully.',
        'created' => 'Position created successfully.',
        'updated' => 'Position updated successfully.',
        'not_found' => 'Position not found.',
        'exception' => 'An error occurred while processing the position.',
    ],

    'audit' => [
        'created' => 'Position created.',
        'updated' => 'Position updated.',
    ],

    'validation' => [
        'attributes' => [
            'name' => 'name',
            'code' => 'code',
            'description' => 'description',
            'is_active' => 'status',
        ],

        'custom' => [
            'name.required' => 'The :attribute field is required.',
            'name.string' => 'The :attribute must be a string.',
            'name.max' => 'The :attribute may not be greater than 255 characters.',
            'name.unique' => 'This :attribute is already in use.',

            'code.string' => 'The :attribute must be a string.',
            'code.max' => 'The :attribute may not be greater than 100 characters.',
            'code.unique' => 'This :attribute is already in use.',

            'description.string' => 'The :attribute must be a string.',

            'is_active.boolean' => 'The :attribute field must be true or false.',
        ],
    ],
];
