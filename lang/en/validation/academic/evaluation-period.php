<?php

return [

    'attributes' => [
        'academic_year_id' => 'academic year',
        'code' => 'code',
        'name' => 'name',
        'description' => 'description',
        'default_order' => 'default order',
        'start_date' => 'start date',
        'end_date' => 'end date',
        'is_active' => 'status',
    ],

    'custom' => [
        'academic_year_id' => [
            'required' => 'The :attribute field is required.',
            'uuid' => 'The :attribute must be a valid UUID.',
            'exists' => 'The selected :attribute is invalid.',
        ],
        'code' => [
            'required' => 'The :attribute field is required.',
            'string' => 'The :attribute must be a string.',
            'max' => 'The :attribute may not be greater than :max characters.',
            'unique' => 'The :attribute has already been taken for this academic year.',
        ],
        'name' => [
            'required' => 'The :attribute field is required.',
            'string' => 'The :attribute must be a string.',
            'max' => 'The :attribute may not be greater than :max characters.',
            'unique' => 'The :attribute has already been taken for this academic year.',
        ],
        'description' => [
            'string' => 'The :attribute must be a string.',
            'max' => 'The :attribute may not be greater than :max characters.',
        ],
        'default_order' => [
            'required' => 'The :attribute field is required.',
            'integer' => 'The :attribute must be an integer.',
            'min' => 'The :attribute must be at least :min.',
            'unique' => 'The :attribute has already been taken for this academic year.',
        ],
        'start_date' => [
            'required' => 'The :attribute field is required.',
            'date' => 'The :attribute is not a valid date.',
        ],
        'end_date' => [
            'required' => 'The :attribute field is required.',
            'date' => 'The :attribute is not a valid date.',
            'after_or_equal' => 'The :attribute must be a date after or equal to the start date.',
        ],
        'is_active' => [
            'boolean' => 'The :attribute field must be true or false.',
        ],
    ],

];
