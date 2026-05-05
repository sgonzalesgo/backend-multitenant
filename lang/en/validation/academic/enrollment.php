<?php

return [

    'attributes' => [
        'enrollment_code' => 'enrollment code',
        'student_id' => 'student',
        'academic_year_id' => 'academic year',
        'course_id' => 'course',
        'parallel_id' => 'parallel',
        'shift_id' => 'shift',
        'enrollment_status_id' => 'enrollment status',
        'assigned_user_id' => 'assigned user',

        'is_new' => 'new enrollment',
        'is_conditional' => 'conditional enrollment',
        'is_active' => 'active',

        'observation' => 'observation',

        'submitted_at' => 'submitted at',
    ],

    'custom' => [
        'student_id.required' => 'The student is required.',
        'student_id.exists' => 'The selected student is invalid.',

        'academic_year_id.required' => 'The academic year is required.',
        'academic_year_id.exists' => 'The selected academic year is invalid.',

        'course_id.exists' => 'The selected course is invalid.',
        'parallel_id.exists' => 'The selected parallel is invalid.',
        'shift_id.exists' => 'The selected shift is invalid.',
        'enrollment_status_id.exists' => 'The selected enrollment status is invalid.',

        'assigned_user_id.exists' => 'The selected user is invalid.',

        'observation.max' => 'The observation may not be greater than 5000 characters.',
    ],

];
