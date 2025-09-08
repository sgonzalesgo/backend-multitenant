<?php

return [
    'attributes' => [
        'provider' => 'provider',
        'token'    => 'token',
    ],
    'custom' => [
        'provider.in'   => 'Provider must be google or facebook.',
        'token.required'=> 'Token is required.',
    ],
];
