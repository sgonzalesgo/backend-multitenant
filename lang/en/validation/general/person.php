<?php

return [
    'custom' => [
        'full_name' => [
            'required' => 'The :attribute field is required.',
            'string' => 'The :attribute field must be a string.',
            'max' => 'The :attribute field may not be greater than :max characters.',
        ],
        'photo' => [
            'file' => 'The :attribute field must be a valid file.',
            'image' => 'The :attribute field must be an image.',
            'mimes' => 'The :attribute field must be a file of type: :values.',
            'max' => 'The :attribute field may not be greater than :max kilobytes.',
        ],
        'email' => [
            'email' => 'The :attribute field must be a valid email address.',
            'max' => 'The :attribute field may not be greater than :max characters.',
        ],
        'phone' => [
            'string' => 'The :attribute field must be a string.',
            'max' => 'The :attribute field may not be greater than :max characters.',
        ],
        'address' => [
            'string' => 'The :attribute field must be a string.',
            'max' => 'The :attribute field may not be greater than :max characters.',
        ],
        'city' => [
            'string' => 'The :attribute field must be a string.',
            'max' => 'The :attribute field may not be greater than :max characters.',
        ],
        'state' => [
            'string' => 'The :attribute field must be a string.',
            'max' => 'The :attribute field may not be greater than :max characters.',
        ],
        'country' => [
            'string' => 'The :attribute field must be a string.',
            'max' => 'The :attribute field may not be greater than :max characters.',
        ],
        'zip' => [
            'string' => 'The :attribute field must be a string.',
            'max' => 'The :attribute field may not be greater than :max characters.',
        ],
        'legal_id' => [
            'required' => 'The :attribute field is required.',
            'string' => 'The :attribute field must be a string.',
            'max' => 'The :attribute field may not be greater than :max characters.',
        ],
        'legal_id_type' => [
            'required' => 'The :attribute field is required.',
            'string' => 'The :attribute field must be a string.',
            'max' => 'The :attribute field may not be greater than :max characters.',
            'unique' => 'A person with this ID type and ID already exists.',
        ],
        'birthday' => [
            'date' => 'The :attribute field must be a valid date.',
        ],
        'gender' => [
            'string' => 'The :attribute field must be a string.',
            'max' => 'The :attribute field may not be greater than :max characters.',
        ],
        'marital_status' => [
            'string' => 'The :attribute field must be a string.',
            'max' => 'The :attribute field may not be greater than :max characters.',
        ],
        'blood_group' => [
            'string' => 'The :attribute field must be a string.',
            'max' => 'The :attribute field may not be greater than :max characters.',
        ],
        'nationality' => [
            'string' => 'The :attribute field must be a string.',
            'max' => 'The :attribute field may not be greater than :max characters.',
        ],
        'status' => [
            'string' => 'The :attribute field must be a string.',
            'max' => 'The :attribute field may not be greater than :max characters.',
        ],
        'deceased_at' => [
            'date' => 'The :attribute field must be a valid date.',
        ],
        'status_changed_at' => [
            'date' => 'The :attribute field must be a valid date.',
        ],

        'has_user' => [
            'boolean' => 'The :attribute field must be true or false.',
        ],
        'user_name' => [
            'required_if' => 'The :attribute field is required when a user must be created or updated.',
            'string' => 'The :attribute field must be a string.',
            'max' => 'The :attribute field may not be greater than :max characters.',
        ],
        'user_email' => [
            'required_if' => 'The :attribute field is required when a user must be created or updated.',
            'email' => 'The :attribute field must be a valid email address.',
            'max' => 'The :attribute field may not be greater than :max characters.',
            'unique' => 'The :attribute has already been taken.',
        ],
        'user_password' => [
            'required_if' => 'The :attribute field is required when a user must be created.',
            'confirmed' => 'The :attribute confirmation does not match.',
        ],
        'user_status' => [
            'string' => 'The :attribute field must be a string.',
            'max' => 'The :attribute field may not be greater than :max characters.',
        ],
    ],

    'attributes' => [
        'full_name' => 'full name',
        'photo' => 'photo',
        'email' => 'email',
        'phone' => 'phone',
        'address' => 'address',
        'city' => 'city',
        'state' => 'state',
        'country' => 'country',
        'zip' => 'zip code',
        'legal_id' => 'legal ID',
        'legal_id_type' => 'legal ID type',
        'birthday' => 'birthday',
        'gender' => 'gender',
        'marital_status' => 'marital status',
        'blood_group' => 'blood group',
        'nationality' => 'nationality',
        'status' => 'status',
        'deceased_at' => 'deceased at',
        'status_changed_at' => 'status changed at',

        'has_user' => 'create user',
        'user_name' => 'user name',
        'user_email' => 'user email',
        'user_password' => 'user password',
        'user_password_confirmation' => 'user password confirmation',
        'user_status' => 'user status',
    ],
];
