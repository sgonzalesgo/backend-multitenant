<?php


return [
    'custom' => [
        'code.required' => 'The code field is required.',
        'code.string' => 'The code must be a string.',
        'code.max' => 'The code may not be greater than :max characters.',
        'code.unique' => 'An enrollment status with this code already exists.',

        'name.required' => 'The name field is required.',
        'name.string' => 'The name must be a string.',
        'name.max' => 'The name may not be greater than :max characters.',
        'name.unique' => 'An enrollment status with this name already exists.',

        'description.string' => 'The description must be a string.',
        'description.max' => 'The description may not be greater than :max characters.',

        'is_active.boolean' => 'The active field must be true or false.',

        'sort_order.integer' => 'The order must be an integer.',
        'sort_order.min' => 'The order must be at least :min.',
    ],

    'attributes' => [
        'code' => 'code',
        'name' => 'name',
        'description' => 'description',
        'is_active' => 'active',
        'sort_order' => 'order',
    ],
];
