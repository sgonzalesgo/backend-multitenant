<?php

return [
    'custom' => [

        'person_id' => [
            'uuid' => 'The :attribute field must be a valid UUID.',
            'exists' => 'The selected :attribute is invalid.',
            'unique' => 'This :attribute is already associated with another user.',
        ],

        'name' => [
            'required' => 'The :attribute field is required.',
            'string' => 'The :attribute field must be a string.',
            'max' => 'The :attribute field may not be greater than :max characters.',
        ],

        'email' => [
            'required' => 'The :attribute field is required.',
            'email' => 'The :attribute field must be a valid email address.',
            'max' => 'The :attribute field may not be greater than :max characters.',
            'unique' => 'The :attribute has already been taken.',
        ],

        'password' => [
            'required' => 'The :attribute field is required.',
            'string' => 'The :attribute field must be a string.',
            'min' => 'The :attribute field must be at least :min characters.',
            'confirmed' => 'The :attribute confirmation does not match.',
        ],

        'status' => [
            'string' => 'The :attribute field must be a string.',
            'max' => 'The :attribute field may not be greater than :max characters.',
        ],

        'locale' => [
            'string' => 'The :attribute field must be a string.',
            'max' => 'The :attribute field may not be greater than :max characters.',
        ],

        'avatar' => [
            'file' => 'The :attribute field must be a valid file.',
            'image' => 'The :attribute field must be an image.',
            'max' => 'The :attribute field may not be greater than :max kilobytes.',
        ],
    ],

    'attributes' => [
        'person_id' => 'person',
        'name' => 'name',
        'email' => 'email',
        'password' => 'password',
        'password_confirmation' => 'password confirmation',
        'status' => 'status',
        'locale' => 'locale',
        'avatar' => 'avatar',
    ],
];
