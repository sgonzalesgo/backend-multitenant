<?php

return [

    'custom' => [

        'academic_year_id' => [
            'required' => 'El año académico es obligatorio.',
            'uuid' => 'El año académico debe ser un UUID válido.',
            'exists' => 'El año académico seleccionado no existe.',
        ],

        'evaluation_period_id' => [
            'uuid' => 'El período de evaluación debe ser un UUID válido.',
            'exists' => 'El período de evaluación seleccionado no existe.',
        ],

        'evaluation_period_ids' => [
            'array' => 'Los períodos de evaluación deben ser un arreglo válido.',
        ],

        'evaluation_period_ids.*' => [
            'uuid' => 'Cada período de evaluación debe ser un UUID válido.',
            'exists' => 'Uno o más períodos de evaluación seleccionados no existen.',
        ],

        'educational_level_id' => [
            'uuid' => 'El nivel educativo debe ser un UUID válido.',
            'exists' => 'El nivel educativo seleccionado no existe.',
        ],

        'course_id' => [
            'uuid' => 'El curso debe ser un UUID válido.',
            'exists' => 'El curso seleccionado no existe.',
        ],

        'specialty_id' => [
            'uuid' => 'La especialidad debe ser un UUID válido.',
            'exists' => 'La especialidad seleccionada no existe.',
        ],

        'parallel_id' => [
            'uuid' => 'El paralelo debe ser un UUID válido.',
            'exists' => 'El paralelo seleccionado no existe.',
        ],

        'modality_id' => [
            'uuid' => 'La modalidad debe ser un UUID válido.',
            'exists' => 'La modalidad seleccionada no existe.',
        ],

        'shift_id' => [
            'uuid' => 'La jornada debe ser un UUID válido.',
            'exists' => 'La jornada seleccionada no existe.',
        ],

        'subject_id' => [
            'uuid' => 'La materia debe ser un UUID válido.',
            'exists' => 'La materia seleccionada no existe.',
        ],

        'instructor_id' => [
            'uuid' => 'El instructor debe ser un UUID válido.',
            'exists' => 'El instructor seleccionado no existe.',
        ],

        'student_id' => [
            'uuid' => 'El estudiante debe ser un UUID válido.',
            'exists' => 'El estudiante seleccionado no existe.',
        ],

        'context' => [
            'in' => 'El contexto seleccionado es inválido.',
        ],
    ],
];
