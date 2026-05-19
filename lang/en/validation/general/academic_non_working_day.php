<?php


return [

    'custom' => [

        'academic_year_id' => [
            'uuid' => 'The academic year must be a valid UUID.',
            'exists' => 'The selected academic year is invalid.',
        ],

        'date' => [
            'required' => 'The date field is required.',
            'date' => 'The date must be a valid date.',
        ],

        'name' => [
            'required' => 'The name field is required.',
            'string' => 'The name must be a string.',
            'max' => 'The name may not be greater than :max characters.',
        ],

        'type' => [
            'required' => 'The type field is required.',
            'string' => 'The type must be a string.',
            'in' => 'The selected type is invalid.',
        ],

        'affects_attendance' => [
            'boolean' => 'The affects attendance field must be true or false.',
        ],

        'affects_calendar' => [
            'boolean' => 'The affects calendar field must be true or false.',
        ],

        'is_active' => [
            'boolean' => 'The active field must be true or false.',
        ],

        'observation' => [
            'string' => 'The observation must be a string.',
            'max' => 'The observation may not be greater than :max characters.',
        ],
    ],

    'attributes' => [
        'academic_year_id' => 'academic year',
        'date' => 'date',
        'name' => 'name',
        'type' => 'type',
        'affects_attendance' => 'affects attendance',
        'affects_calendar' => 'affects calendar',
        'is_active' => 'active',
        'observation' => 'observation',
    ],
];
