<?php

return [
    'custom' => [
        'user_ids.required' => 'You must select at least one user.',
        'user_ids.array' => 'The users field must be a list.',
        'user_ids.min' => 'You must select at least one user.',
        'user_ids.*.required' => 'Each user is required.',
        'user_ids.*.uuid' => 'Each user must be a valid UUID.',
        'user_ids.*.distinct' => 'You cannot repeat users.',
        'user_ids.*.exists' => 'One or more users do not exist.',
        'title.required' => 'The title is required.',
        'title.string' => 'The title must be text.',
        'title.max' => 'The title may not be greater than :max characters.',
        'message.required' => 'The message is required.',
        'message.string' => 'The message must be text.',
        'message.max' => 'The message may not be greater than :max characters.',
        'route.string' => 'The route must be text.',
        'route.max' => 'The route may not be greater than :max characters.',
        'payload.array' => 'The payload must be a valid object.',
    ],
    'attributes' => [
        'user_ids' => 'users',
        'user_ids.*' => 'user',
        'title' => 'title',
        'message' => 'message',
        'route' => 'route',
        'payload' => 'payload',
    ],
];
