<?php


return [

    'attributes' => [
        'academic_year_id' => 'academic year',
        'course_id' => 'course',
        'parallel_id' => 'parallel',
        'modality_id' => 'modality',
        'shift_id' => 'shift',

        'status' => 'status',
        'general_observation' => 'general observation',

        'check_conflicts' => 'check conflicts',

        'frequencies' => 'frequencies',

        'frequencies.*.day_of_week' => 'day of week',
        'frequencies.*.start_time' => 'start time',
        'frequencies.*.end_time' => 'end time',
        'frequencies.*.classroom_id' => 'classroom',
        'frequencies.*.subject_id' => 'subject',
        'frequencies.*.instructor_id' => 'instructor',
        'frequencies.*.observation' => 'observation',
    ],

    'custom' => [
        'academic_year_id.required' => 'The academic year is required.',
        'course_id.required' => 'The course is required.',
        'parallel_id.required' => 'The parallel is required.',
        'modality_id.required' => 'The modality is required.',
        'shift_id.required' => 'The shift is required.',

        'frequencies.required' => 'At least one frequency is required.',
        'frequencies.array' => 'The frequencies must be a valid list.',
        'frequencies.min' => 'At least one frequency is required.',

        'frequencies.*.day_of_week.required' => 'The day of week is required.',
        'frequencies.*.start_time.required' => 'The start time is required.',
        'frequencies.*.end_time.required' => 'The end time is required.',

        'frequencies.*.classroom_id.required' => 'The classroom is required.',
        'frequencies.*.subject_id.required' => 'The subject is required.',
        'frequencies.*.instructor_id.required' => 'The instructor is required.',
    ],

];
