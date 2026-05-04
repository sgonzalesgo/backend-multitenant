<?php


return [

    'attributes' => [
        'educational_level_id' => 'educational level',
        'instructor_id' => 'instructor',
        'code' => 'code',
        'name' => 'name',
        'description' => 'description',
        'capacity' => 'capacity',
        'credits' => 'credits',
        'theoretical_hours' => 'theoretical hours',
        'practical_hours' => 'practical hours',
        'total_hours' => 'total hours',
        'status' => 'status',
        'notes' => 'notes',
    ],

    'custom' => [
        'educational_level_id.required' => 'The educational level is required.',
        'educational_level_id.exists' => 'The selected educational level is invalid.',

        'instructor_id.required' => 'The instructor is required.',
        'instructor_id.exists' => 'The selected instructor is invalid.',

        'code.required' => 'The code is required.',
        'code.max' => 'The code may not be greater than 50 characters.',

        'name.required' => 'The name is required.',

        'capacity.required' => 'The capacity is required.',
        'capacity.integer' => 'The capacity must be a number.',
        'capacity.min' => 'The capacity must be at least 0.',

        'credits.integer' => 'The credits must be a number.',
        'theoretical_hours.integer' => 'The theoretical hours must be a number.',
        'practical_hours.integer' => 'The practical hours must be a number.',
        'total_hours.integer' => 'The total hours must be a number.',
    ],

];
