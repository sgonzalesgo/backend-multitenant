<?php

return [

    'custom' => [

        'academic_year_id' => [
            'required' => 'The :attribute field is required.',
            'uuid' => 'The :attribute must be a valid UUID.',
            'exists' => 'The selected :attribute is invalid.',
        ],

        'course_id' => [
            'required' => 'The :attribute field is required.',
            'uuid' => 'The :attribute must be a valid UUID.',
            'exists' => 'The selected :attribute is invalid.',
        ],

        'parallel_id' => [
            'required' => 'The :attribute field is required.',
            'uuid' => 'The :attribute must be a valid UUID.',
            'exists' => 'The selected :attribute is invalid.',
        ],

        'subject_id' => [
            'required' => 'The :attribute field is required.',
            'uuid' => 'The :attribute must be a valid UUID.',
            'exists' => 'The selected :attribute is invalid.',
        ],

        'attendance_date' => [
            'required' => 'The :attribute field is required.',
            'date' => 'The :attribute must be a valid date.',
        ],

        'observation' => [
            'string' => 'The :attribute must be a string.',
            'max' => 'The :attribute may not be greater than :max characters.',
        ],

        'close' => [
            'boolean' => 'The :attribute field must be true or false.',
        ],

        'records' => [
            'required' => 'The :attribute field is required.',
            'array' => 'The :attribute must be a valid array.',
            'min' => 'At least one attendance record is required.',
        ],
    ],

    'attributes' => [
        'academic_year_id' => 'academic year',
        'course_id' => 'course',
        'parallel_id' => 'parallel',
        'subject_id' => 'subject',
        'attendance_date' => 'attendance date',

        'observation' => 'observation',
        'close' => 'close attendance',

        'records' => 'attendance records',
    ],
];
