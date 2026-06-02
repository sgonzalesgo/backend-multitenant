<?php


return [
    'custom' => [
        'name' => [
            'required' => 'The :attribute field is required.',
            'string' => 'The :attribute must be a string.',
            'max' => 'The :attribute may not be greater than :max characters.',
            'unique' => 'The :attribute has already been taken for this school.',
        ],
        'description' => [
            'string' => 'The :attribute must be a string.',
            'max' => 'The :attribute may not be greater than :max characters.',
        ],
        'educational_level_id' => [
            'uuid' => 'The :attribute must be a valid UUID.',
            'exists' => 'The selected :attribute is invalid.',
        ],
        'course_id' => [
            'uuid' => 'The :attribute must be a valid UUID.',
            'exists' => 'The selected :attribute is invalid.',
        ],
        'evaluation_period_id' => [
            'uuid' => 'The :attribute must be a valid UUID.',
            'exists' => 'The selected :attribute is invalid.',
        ],
        'is_active' => [
            'boolean' => 'The :attribute field must be true or false.',
        ],
        'skill_definition_ids' => [
            'required' => 'You must select at least one :attribute.',
            'array' => 'The :attribute field must be a list.',
            'min' => 'You must select at least one :attribute.',
        ],
        'skill_definition_ids.*' => [
            'required' => 'Each selected skill is required.',
            'uuid' => 'Each selected skill must be a valid UUID.',
            'exists' => 'One or more selected skills are invalid.',
        ],
    ],

    'attributes' => [
        'name' => 'name',
        'description' => 'description',
        'educational_level_id' => 'educational level',
        'course_id' => 'course',
        'evaluation_period_id' => 'evaluation period',
        'is_active' => 'active status',
        'skill_definition_ids' => 'skills',
        'skill_definition_ids.*' => 'skill',
    ],
];
