<?php


return [
    'attributes' => [
        'name' => 'name',
        'email' => 'email',
        'password' => 'password',
        'password_confirmation' => 'password confirmation',
        'locale' => 'locale',
        'avatar' => 'avatar',
        'is_active' => 'active',
    ],
    'custom' => [
        'email.unique' => 'An account with this email already exists.',
        'password.confirmed' => 'Password confirmation does not match.',
    ],
];
