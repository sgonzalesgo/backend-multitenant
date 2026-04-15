<?php

return [
    'custom' => [
        'name' => [
            'required' => 'The :attribute field is required.',
            'string' => 'The :attribute field must be a string.',
            'max' => 'The :attribute field may not be greater than :max characters.',
        ],

        'code' => [
            'required' => 'The :attribute field is required.',
            'string' => 'The :attribute field must be a string.',
            'max' => 'The :attribute field may not be greater than :max characters.',
        ],

        'person_id' => [
            'uuid' => 'The :attribute field must be a valid UUID.',
            'exists' => 'The selected :attribute is invalid.',
        ],

        'status' => [
            'string' => 'The :attribute field must be a string.',
            'max' => 'The :attribute field may not be greater than :max characters.',
        ],
    ],

    'attributes' => [
        'name' => 'name',
        'code' => 'code',
        'person_id' => 'manager',
        'status' => 'status',
    ],
];
