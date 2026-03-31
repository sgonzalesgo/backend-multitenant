<?php


return [
    'custom' => [
        'email.required' => 'Email is required.',
        'email.email' => 'You must enter a valid email address.',
        'email.max' => 'The email may not be greater than :max characters.',

        'code.required' => 'The code is required.',
        'code.digits' => 'The code must be exactly 6 digits.',

        'password.required' => 'Password is required.',
        'password.min' => 'The password must be at least :min characters.',
        'password.confirmed' => 'The password confirmation does not match.',
    ],
    'attributes' => [
        'email' => 'email',
        'code' => 'code',
        'password' => 'password',
    ],
];
