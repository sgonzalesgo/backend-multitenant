<?php
return [
    'attributes' => [
        'provider'     => 'provider',
        'access_token' => 'access token',
        'email'        => 'email',
        'name'         => 'name',
        'avatar'       => 'avatar',
        'locale'       => 'locale',
    ],
    'custom' => [
        'provider.in'       => 'Provider must be google or facebook.',
        'access_token.required' => 'Access token is required.',
        'access_token.min'  => 'Access token looks invalid.',
        'email.email'       => 'Email is invalid.',
        'avatar.url'        => 'Avatar must be a valid URL.',
    ],
];
