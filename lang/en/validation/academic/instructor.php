<?php

return [
    'custom' => [

        // Instructor
        'department_id' => [
            'uuid' => 'The :attribute must be a valid UUID.',
            'exists' => 'The selected :attribute is invalid.',
        ],
        'academic_title' => [
            'string' => 'The :attribute must be a string.',
            'max' => 'The :attribute may not be greater than :max characters.',
        ],
        'academic_level' => [
            'string' => 'The :attribute must be a string.',
            'max' => 'The :attribute may not be greater than :max characters.',
        ],

    ],

    'attributes' => [

        // Instructor
        'department_id' => 'department',
        'academic_title' => 'academic title',
        'academic_level' => 'academic level',

    ],
];
