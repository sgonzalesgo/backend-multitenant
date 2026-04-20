<?php


return [

    'attributes' => [
        'code' => 'code',
        'name' => 'name',
        'description' => 'description',
        'default_order' => 'default order',
        'is_active' => 'status',
    ],

    'custom' => [
        'code' => [
            'required' => 'The :attribute is required.',
            'string' => 'The :attribute must be a string.',
            'max' => 'The :attribute may not be greater than :max characters.',
            'unique' => 'The :attribute has already been taken.',
        ],
        'name' => [
            'required' => 'The :attribute is required.',
            'string' => 'The :attribute must be a string.',
            'max' => 'The :attribute may not be greater than :max characters.',
            'unique' => 'The :attribute has already been taken.',
        ],
        'description' => [
            'string' => 'The :attribute must be a string.',
            'max' => 'The :attribute may not be greater than :max characters.',
        ],
        'default_order' => [
            'required' => 'The :attribute is required.',
            'integer' => 'The :attribute must be a number.',
            'min' => 'The :attribute must be at least :min.',
        ],
        'is_active' => [
            'boolean' => 'The :attribute must be true or false.',
        ],
    ],

];
