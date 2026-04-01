<?php

return [
    'custom' => [
        'per_page.integer' => 'The per_page field must be an integer.',
        'per_page.min' => 'The per_page field must be at least :min.',
        'per_page.max' => 'The per_page field may not be greater than :max.',
        'q.string' => 'The search field must be text.',
        'q.max' => 'The search field may not be greater than :max characters.',
        'archived.string' => 'The archived filter must be text.',
        'archived.in' => 'The archived filter must be one of: without, only, with.',
    ],
    'attributes' => [
        'per_page' => 'per page',
        'q' => 'search',
        'archived' => 'archived filter',
    ],
];
