<?php

return [
    'custom' => [
        'title' => [
            'required' => 'The :attribute field is required.',
            'string' => 'The :attribute field must be a string.',
            'max' => 'The :attribute field may not be greater than :max characters.',
        ],

        'description' => [
            'required' => 'The :attribute field is required.',
            'string' => 'The :attribute field must be a string.',
        ],

        'category' => [
            'string' => 'The :attribute field must be a string.',
            'enum' => 'The selected :attribute is invalid.',
        ],

        'priority' => [
            'string' => 'The :attribute field must be a string.',
            'enum' => 'The selected :attribute is invalid.',
        ],

        'status' => [
            'required' => 'The :attribute field is required.',
            'string' => 'The :attribute field must be a string.',
            'enum' => 'The selected :attribute is invalid.',
        ],

        'assigned_to_id' => [
            'required' => 'The :attribute field is required.',
            'uuid' => 'The :attribute field must be a valid UUID.',
            'exists' => 'The selected :attribute is invalid.',
        ],

        'comment' => [
            'required' => 'The :attribute field is required.',
            'string' => 'The :attribute field must be a string.',
        ],

        'is_internal' => [
            'boolean' => 'The :attribute field must be true or false.',
        ],

        'attachments' => [
            'array' => 'The :attribute field must be an array.',
        ],

        'attachments.*' => [
            'file' => 'Each :attribute must be a valid file.',
            'max' => 'Each :attribute may not be greater than :max kilobytes.',
        ],
    ],

    'attributes' => [
        'title' => 'title',
        'description' => 'description',
        'category' => 'category',
        'priority' => 'priority',
        'status' => 'status',
        'assigned_to_id' => 'assigned user',
        'comment' => 'comment',
        'is_internal' => 'internal comment',
        'attachments' => 'attachments',
        'attachments.*' => 'attachment',
    ],
];
