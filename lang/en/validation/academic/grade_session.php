<?php


return [
    'attributes' => [
        'academic_year_id' => 'academic year',
        'evaluation_period_id' => 'evaluation period',
        'course_id' => 'course',
        'specialty_id' => 'specialty',
        'parallel_id' => 'parallel',
        'modality_id' => 'modality',
        'shift_id' => 'shift',
        'subject_id' => 'subject',
        'instructor_id' => 'instructor',

        'records' => 'records',
        'records.*.grade_record_id' => 'grade record',
        'records.*.components' => 'components',
        'records.*.components.*.grade_record_component_id' => 'grade record component',
        'records.*.components.*.score' => 'score',
        'records.*.components.*.qualitative_grade' => 'qualitative grade',
        'records.*.components.*.observation' => 'observation',
        'status' => 'status',
        'q' => 'search',
        'per_page' => 'records per page',
    ],
];
