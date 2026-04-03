<?php

return [
    'custom' => [
        'person_id' => [
            'unique_in_tenant' => 'The person is already registered as an instructor in the current tenant.',
        ],
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
        'legal_id' => [
            'required' => 'The :attribute field is required.',
            'string' => 'The :attribute field must be a string.',
            'max' => 'The :attribute field may not be greater than :max characters.',
        ],
        'legal_id_type' => [
            'required' => 'The :attribute field is required.',
            'string' => 'The :attribute field must be a string.',
            'max' => 'The :attribute field may not be greater than :max characters.',
        ],
        'birthday' => [
            'date' => 'The :attribute field must be a valid date.',
        ],
        'deceased_at' => [
            'date' => 'The :attribute field must be a valid date.',
        ],
        'person_status_changed_at' => [
            'date' => 'The :attribute field must be a valid date.',
        ],
        'status_changed_at' => [
            'date' => 'The :attribute field must be a valid date.',
        ],
        'code' => [
            'string' => 'The :attribute field must be a string.',
            'max' => 'The :attribute field may not be greater than :max characters.',
        ],
        'academic_title' => [
            'string' => 'The :attribute field must be a string.',
            'max' => 'The :attribute field may not be greater than :max characters.',
        ],
        'academic_level' => [
            'string' => 'The :attribute field must be a string.',
            'max' => 'The :attribute field may not be greater than :max characters.',
        ],
        'specialty' => [
            'string' => 'The :attribute field must be a string.',
            'max' => 'The :attribute field may not be greater than :max characters.',
        ],
        'status' => [
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
        'person_status' => 'person status',
        'deceased_at' => 'deceased at',
        'person_status_changed_at' => 'person status changed at',
        'code' => 'code',
        'academic_title' => 'academic title',
        'academic_level' => 'academic level',
        'specialty' => 'specialty',
        'status' => 'status',
        'status_changed_at' => 'status changed at',
    ],
];
