<?php


return [
    'list' => [
        'custom' => [
            'per_page.integer' => 'The per page value must be a number.',
            'per_page.min' => 'The per page value must be at least 1.',
            'per_page.max' => 'The per page value may not be greater than 100.',
            'sort_by.in' => 'The selected sort by value is invalid.',
            'sort_direction.in' => 'The selected sort direction is invalid.',
        ],
        'attributes' => [
            'per_page' => 'per page',
            'q' => 'search',
            'is_active' => 'active status',
            'is_system' => 'system status',
            'category' => 'category',
            'paginate' => 'paginate',
            'sort_by' => 'sort by',
            'sort_direction' => 'sort direction',
        ],
    ],

    'store' => [
        'custom' => [
            'code.required' => 'The code field is required.',
            'code.max' => 'The code may not be greater than 100 characters.',
            'name.required' => 'The name field is required.',
            'name.max' => 'The name may not be greater than 150 characters.',
            'color.max' => 'The color may not be greater than 20 characters.',
            'icon.max' => 'The icon may not be greater than 100 characters.',
        ],
        'attributes' => [
            'code' => 'code',
            'name' => 'name',
            'description' => 'description',
            'color' => 'color',
            'icon' => 'icon',
            'is_active' => 'active status',
            'settings' => 'settings',
            'settings.category' => 'category',
            'settings.requires_location' => 'requires location',
            'settings.requires_audience' => 'requires audience',
            'settings.supports_attendance' => 'supports attendance',
            'settings.supports_response' => 'supports response',
            'settings.supports_recurrence' => 'supports recurrence',
            'settings.default_all_day' => 'default all day',
        ],
    ],

    'update' => [
        'custom' => [
            'code.required' => 'The code field is required.',
            'code.max' => 'The code may not be greater than 100 characters.',
            'name.required' => 'The name field is required.',
            'name.max' => 'The name may not be greater than 150 characters.',
            'color.max' => 'The color may not be greater than 20 characters.',
            'icon.max' => 'The icon may not be greater than 100 characters.',
        ],
        'attributes' => [
            'code' => 'code',
            'name' => 'name',
            'description' => 'description',
            'color' => 'color',
            'icon' => 'icon',
            'is_active' => 'active status',
            'settings' => 'settings',
            'settings.category' => 'category',
            'settings.requires_location' => 'requires location',
            'settings.requires_audience' => 'requires audience',
            'settings.supports_attendance' => 'supports attendance',
            'settings.supports_response' => 'supports response',
            'settings.supports_recurrence' => 'supports recurrence',
            'settings.default_all_day' => 'default all day',
        ],
    ],
];
