<?php

return [

    'custom' => [

        'academic_year_id' => [
            'required' => 'The academic year is required.',
            'uuid' => 'The academic year must be a valid UUID.',
            'exists' => 'The selected academic year does not exist.',
        ],

        'evaluation_period_id' => [
            'uuid' => 'The evaluation period must be a valid UUID.',
            'exists' => 'The selected evaluation period does not exist.',
        ],

        'evaluation_period_ids' => [
            'array' => 'The evaluation periods must be a valid array.',
        ],

        'evaluation_period_ids.*' => [
            'uuid' => 'Each evaluation period must be a valid UUID.',
            'exists' => 'One or more selected evaluation periods do not exist.',
        ],

        'educational_level_id' => [
            'uuid' => 'The educational level must be a valid UUID.',
            'exists' => 'The selected educational level does not exist.',
        ],

        'course_id' => [
            'uuid' => 'The course must be a valid UUID.',
            'exists' => 'The selected course does not exist.',
        ],

        'specialty_id' => [
            'uuid' => 'The specialty must be a valid UUID.',
            'exists' => 'The selected specialty does not exist.',
        ],

        'parallel_id' => [
            'uuid' => 'The parallel must be a valid UUID.',
            'exists' => 'The selected parallel does not exist.',
        ],

        'modality_id' => [
            'uuid' => 'The modality must be a valid UUID.',
            'exists' => 'The selected modality does not exist.',
        ],

        'shift_id' => [
            'uuid' => 'The shift must be a valid UUID.',
            'exists' => 'The selected shift does not exist.',
        ],

        'subject_id' => [
            'uuid' => 'The subject must be a valid UUID.',
            'exists' => 'The selected subject does not exist.',
        ],

        'instructor_id' => [
            'uuid' => 'The instructor must be a valid UUID.',
            'exists' => 'The selected instructor does not exist.',
        ],

        'student_id' => [
            'uuid' => 'The student must be a valid UUID.',
            'exists' => 'The selected student does not exist.',
        ],

        'context' => [
            'in' => 'The selected context is invalid.',
        ],
    ],
];
