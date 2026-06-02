<?php


return [
    'custom' => [
        'academic_year_id' => [
            'required' => 'The :attribute field is required.',
            'uuid' => 'The :attribute must be a valid UUID.',
            'exists' => 'The selected :attribute is invalid.',
        ],
        'evaluation_period_id' => [
            'required' => 'The :attribute field is required.',
            'uuid' => 'The :attribute must be a valid UUID.',
            'exists' => 'The selected :attribute is invalid.',
        ],
        'course_id' => [
            'required' => 'The :attribute field is required.',
            'uuid' => 'The :attribute must be a valid UUID.',
            'exists' => 'The selected :attribute is invalid.',
        ],
        'modality_id' => [
            'uuid' => 'The :attribute must be a valid UUID.',
            'exists' => 'The selected :attribute is invalid.',
        ],
        'shift_id' => [
            'uuid' => 'The :attribute must be a valid UUID.',
            'exists' => 'The selected :attribute is invalid.',
        ],
        'parallel_ids' => [
            'required' => 'You must select at least one :attribute.',
            'array' => 'The :attribute field must be a list.',
            'min' => 'You must select at least one :attribute.',
        ],
        'parallel_ids.*' => [
            'required' => 'Each selected parallel is required.',
            'uuid' => 'Each selected parallel must be a valid UUID.',
            'exists' => 'One or more selected parallels are invalid.',
        ],
        'subject_ids' => [
            'required' => 'You must select at least one :attribute.',
            'array' => 'The :attribute field must be a list.',
            'min' => 'You must select at least one :attribute.',
        ],
        'subject_ids.*' => [
            'required' => 'Each selected subject is required.',
            'uuid' => 'Each selected subject must be a valid UUID.',
            'exists' => 'One or more selected subjects are invalid.',
        ],
        'qualitative_evaluation_template_id' => [
            'required' => 'The :attribute field is required.',
            'uuid' => 'The :attribute must be a valid UUID.',
            'exists' => 'The selected :attribute is invalid.',
        ],
    ],

    'attributes' => [
        'academic_year_id' => 'academic year',
        'evaluation_period_id' => 'evaluation period',
        'course_id' => 'course',
        'modality_id' => 'modality',
        'shift_id' => 'shift',
        'parallel_ids' => 'parallels',
        'parallel_ids.*' => 'parallel',
        'subject_ids' => 'subjects',
        'subject_ids.*' => 'subject',
        'qualitative_evaluation_template_id' => 'qualitative evaluation template',
    ],
];
