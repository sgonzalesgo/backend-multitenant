<?php

return [
    'attributes' => [
        'name' => 'permission name',
        'guard_name' => 'guard',
        'id' => 'identifier',
    ],
    'custom' => [
        'name.required' => 'The :attribute is required.',
        'name.string' => 'The :attribute must be a string.',
        'name.max' => 'The :attribute may not be greater than :max characters.',
        'name.unique' => 'A permission with this name already exists for the specified guard.',
        'guard_name.string' => 'The :attribute must be a string.',
        'guard_name.max' => 'The :attribute may not be greater than :max characters.',
    ],
];
