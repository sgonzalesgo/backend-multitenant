<?php

return [
    'attributes' => [
        'name' => 'name',
        'email' => 'email',
        'password' => 'password',
        'password_confirmation' => 'password confirmation',
        'locale' => 'locale',
        'avatar' => 'avatar',
    ],

    'custom' => [
        'name.required' => 'The :attribute field is required.',
        'email.required' => 'The :attribute field is required.',
        'email.email' => 'The :attribute must be a valid email address.',
        'email.unique' => 'An account with this email already exists.',

        'password.required' => 'The :attribute field is required.',
        'password.min' => 'The :attribute must be at least :min characters.',
        'password.confirmed' => 'The password confirmation does not match.',

        'avatar.image' => 'The file must be a valid image.',
        'avatar.max' => 'The image must not be greater than :max KB.',
    ],
];
