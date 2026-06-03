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

        'specialty_id' => [
            'uuid' => 'The :attribute must be a valid UUID.',
            'exists' => 'The selected :attribute is invalid.',
        ],

        'parallel_id' => [
            'required' => 'The :attribute field is required.',
            'uuid' => 'The :attribute must be a valid UUID.',
            'exists' => 'The selected :attribute is invalid.',
        ],

        'modality_id' => [
            'required' => 'The :attribute field is required.',
            'uuid' => 'The :attribute must be a valid UUID.',
            'exists' => 'The selected :attribute is invalid.',
        ],

        'shift_id' => [
            'required' => 'The :attribute field is required.',
            'uuid' => 'The :attribute must be a valid UUID.',
            'exists' => 'The selected :attribute is invalid.',
        ],

        'subject_id' => [
            'required' => 'The :attribute field is required.',
            'uuid' => 'The :attribute must be a valid UUID.',
            'exists' => 'The selected :attribute is invalid.',
        ],

        'name' => [
            'string' => 'The :attribute must be a string.',
            'max' => 'The :attribute may not be greater than :max characters.',
        ],

        'qualitative_evaluation_session_id' => [
            'required' => 'The :attribute field is required.',
            'uuid' => 'The :attribute must be a valid UUID.',
            'exists' => 'The selected :attribute is invalid.',
        ],

        'records' => [
            'required' => 'The :attribute field is required.',
            'array' => 'The :attribute must be an array.',
            'min' => 'The :attribute must contain at least one record.',
        ],

        'records.*.student_id' => [
            'required' => 'The student field is required.',
            'uuid' => 'The student must be a valid UUID.',
            'exists' => 'The selected student is invalid.',
        ],

        'records.*.skills' => [
            'required' => 'The skills field is required.',
            'array' => 'The skills must be an array.',
            'min' => 'The skills must contain at least one record.',
        ],

        'records.*.skills.*.qualitative_evaluation_component_id' => [
            'required' => 'The skill field is required.',
            'uuid' => 'The skill must be a valid UUID.',
            'exists' => 'The selected skill is invalid.',
        ],

        'records.*.skills.*.value' => [
            'in' => 'The selected :attribute is invalid.',
        ],

        'records.*.skills.*.observation' => [
            'string' => 'The :attribute must be a string.',
            'max' => 'The :attribute may not be greater than :max characters.',
        ],
    ],

    'attributes' => [
        'academic_year_id' => 'academic year',
        'evaluation_period_id' => 'evaluation period',
        'course_id' => 'course',
        'specialty_id' => 'specialty',
        'parallel_id' => 'parallel',
        'modality_id' => 'modality',
        'shift_id' => 'shift',
        'subject_id' => 'subject',
        'name' => 'name',
        'qualitative_evaluation_session_id' => 'qualitative evaluation session',
        'records' => 'records',
        'value' => 'value',
        'observation' => 'observation',
    ],
];
