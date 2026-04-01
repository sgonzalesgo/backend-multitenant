<?php

return [
    'list' => [
        'custom' => [
            'end.after_or_equal' => 'The end date must be a date after or equal to the start date.',
            'event_type_id.uuid' => 'The event type is invalid.',
            'status.in' => 'The selected status is invalid.',
            'visibility.in' => 'The selected visibility is invalid.',
        ],
        'attributes' => [
            'start' => 'start date',
            'end' => 'end date',
            'event_type_id' => 'event type',
            'created_by_me' => 'created by me',
            'status' => 'status',
            'visibility' => 'visibility',
            'search' => 'search',
        ],
    ],

    'store' => [
        'custom' => [
            'title.required' => 'The title field is required.',
            'title.max' => 'The title may not be greater than 255 characters.',
            'url.url' => 'The event URL must be a valid URL.',
            'start_at.required' => 'The start date is required.',
            'end_at.after_or_equal' => 'The end date must be after or equal to the start date.',
            'status.in' => 'The selected status is invalid.',
            'visibility.in' => 'The selected visibility is invalid.',
            'editable_by.in' => 'The selected edit mode is invalid.',
        ],
        'attributes' => [
            'title' => 'title',
            'description' => 'description',
            'location' => 'location',
            'url' => 'event URL',
            'start_at' => 'start date',
            'end_at' => 'end date',
            'all_day' => 'all day',
            'timezone' => 'timezone',
            'status' => 'status',
            'visibility' => 'visibility',
            'editable_by' => 'edit mode',
            'color' => 'color',
            'participants' => 'participants',
            'audiences' => 'audiences',
        ],
    ],

    'update' => [
        'custom' => [
            'title.required' => 'The title field is required.',
            'url.url' => 'The event URL must be a valid URL.',
            'end_at.after_or_equal' => 'The end date must be after or equal to the start date.',
            'status.in' => 'The selected status is invalid.',
            'visibility.in' => 'The selected visibility is invalid.',
            'editable_by.in' => 'The selected edit mode is invalid.',
        ],
        'attributes' => [
            'title' => 'title',
            'description' => 'description',
            'location' => 'location',
            'url' => 'event URL',
            'start_at' => 'start date',
            'end_at' => 'end date',
            'all_day' => 'all day',
            'timezone' => 'timezone',
            'status' => 'status',
            'visibility' => 'visibility',
            'editable_by' => 'edit mode',
            'color' => 'color',
            'participants' => 'participants',
            'audiences' => 'audiences',
        ],
    ],
];
