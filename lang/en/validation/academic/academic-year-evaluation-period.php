<?php

return [

    'attributes' => [
        'academic_year_id' => 'academic year',
        'evaluation_period_id' => 'evaluation period',
        'order' => 'order',
        'start_date' => 'start date',
        'end_date' => 'end date',
        'is_active' => 'status',
    ],

    'custom' => [
        'academic_year_id' => [
            'required' => 'The :attribute is required.',
            'uuid' => 'The :attribute must be a valid UUID.',
            'exists' => 'The selected :attribute is invalid.',
        ],
        'evaluation_period_id' => [
            'required' => 'The :attribute is required.',
            'uuid' => 'The :attribute must be a valid UUID.',
            'exists' => 'The selected :attribute is invalid.',
            'unique' => 'The selected :attribute has already been assigned to this academic year.',
        ],
        'order' => [
            'required' => 'The :attribute is required.',
            'integer' => 'The :attribute must be a number.',
            'min' => 'The :attribute must be at least :min.',
            'unique' => 'The :attribute has already been taken for this academic year.',
        ],
        'start_date' => [
            'required' => 'The :attribute is required.',
            'date' => 'The :attribute must be a valid date.',
        ],
        'end_date' => [
            'required' => 'The :attribute is required.',
            'date' => 'The :attribute must be a valid date.',
            'after' => 'The :attribute must be a date after :date.',
        ],
        'is_active' => [
            'boolean' => 'The :attribute must be true or false.',
        ],
    ],

    'messages' => [
        'date_overlap' => 'The selected date range overlaps with another evaluation period in this academic year.',
        'duplicate_evaluation_period' => 'The selected evaluation period is duplicated in the request.',
        'duplicate_order' => 'The order is duplicated in the request.',
    ],

];
