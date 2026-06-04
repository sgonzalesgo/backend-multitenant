<?php


return [
    'attributes' => [
        'academic_year_id' => 'año académico',
        'evaluation_period_id' => 'período de evaluación',
        'course_id' => 'curso',
        'specialty_id' => 'especialidad',
        'parallel_id' => 'paralelo',
        'modality_id' => 'modalidad',
        'shift_id' => 'jornada',
        'subject_id' => 'asignatura',
        'instructor_id' => 'docente',

        'records' => 'registros',
        'records.*.grade_record_id' => 'registro de nota',
        'records.*.components' => 'componentes',
        'records.*.components.*.grade_record_component_id' => 'componente del registro',
        'records.*.components.*.score' => 'nota',
        'records.*.components.*.qualitative_grade' => 'calificación cualitativa',
        'records.*.components.*.observation' => 'observación',
        'status' => 'estado',
        'q' => 'búsqueda',
        'per_page' => 'registros por página',
    ],
];
