<?php


return [
    'custom' => [
        'academic_year_id' => [
            'required' => 'El campo :attribute es obligatorio.',
            'uuid' => 'El campo :attribute debe ser un UUID válido.',
            'exists' => 'El :attribute seleccionado no es válido.',
        ],
        'evaluation_period_id' => [
            'required' => 'El campo :attribute es obligatorio.',
            'uuid' => 'El campo :attribute debe ser un UUID válido.',
            'exists' => 'El :attribute seleccionado no es válido.',
        ],
        'course_id' => [
            'required' => 'El campo :attribute es obligatorio.',
            'uuid' => 'El campo :attribute debe ser un UUID válido.',
            'exists' => 'El :attribute seleccionado no es válido.',
        ],
        'modality_id' => [
            'uuid' => 'El campo :attribute debe ser un UUID válido.',
            'exists' => 'El :attribute seleccionado no es válido.',
        ],
        'shift_id' => [
            'uuid' => 'El campo :attribute debe ser un UUID válido.',
            'exists' => 'El :attribute seleccionado no es válido.',
        ],
        'parallel_ids' => [
            'required' => 'Debe seleccionar al menos un :attribute.',
            'array' => 'El campo :attribute debe ser una lista.',
            'min' => 'Debe seleccionar al menos un :attribute.',
        ],
        'parallel_ids.*' => [
            'required' => 'Cada paralelo seleccionado es obligatorio.',
            'uuid' => 'Cada paralelo seleccionado debe ser un UUID válido.',
            'exists' => 'Uno o más paralelos seleccionados no son válidos.',
        ],
        'subject_ids' => [
            'required' => 'Debe seleccionar al menos una :attribute.',
            'array' => 'El campo :attribute debe ser una lista.',
            'min' => 'Debe seleccionar al menos una :attribute.',
        ],
        'subject_ids.*' => [
            'required' => 'Cada asignatura seleccionada es obligatoria.',
            'uuid' => 'Cada asignatura seleccionada debe ser un UUID válido.',
            'exists' => 'Una o más asignaturas seleccionadas no son válidas.',
        ],
        'qualitative_evaluation_template_id' => [
            'required' => 'El campo :attribute es obligatorio.',
            'uuid' => 'El campo :attribute debe ser un UUID válido.',
            'exists' => 'El :attribute seleccionado no es válido.',
        ],
    ],

    'attributes' => [
        'academic_year_id' => 'año académico',
        'evaluation_period_id' => 'período de evaluación',
        'course_id' => 'curso',
        'modality_id' => 'modalidad',
        'shift_id' => 'jornada',
        'parallel_ids' => 'paralelo',
        'parallel_ids.*' => 'paralelo',
        'subject_ids' => 'asignatura',
        'subject_ids.*' => 'asignatura',
        'qualitative_evaluation_template_id' => 'plantilla de evaluación cualitativa',
    ],
];
