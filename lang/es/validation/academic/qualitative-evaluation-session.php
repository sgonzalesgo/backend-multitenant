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

        'specialty_id' => [
            'uuid' => 'El campo :attribute debe ser un UUID válido.',
            'exists' => 'El :attribute seleccionado no es válido.',
        ],

        'parallel_id' => [
            'required' => 'El campo :attribute es obligatorio.',
            'uuid' => 'El campo :attribute debe ser un UUID válido.',
            'exists' => 'El :attribute seleccionado no es válido.',
        ],

        'modality_id' => [
            'required' => 'El campo :attribute es obligatorio.',
            'uuid' => 'El campo :attribute debe ser un UUID válido.',
            'exists' => 'El :attribute seleccionado no es válido.',
        ],

        'shift_id' => [
            'required' => 'El campo :attribute es obligatorio.',
            'uuid' => 'El campo :attribute debe ser un UUID válido.',
            'exists' => 'El :attribute seleccionado no es válido.',
        ],

        'subject_id' => [
            'required' => 'El campo :attribute es obligatorio.',
            'uuid' => 'El campo :attribute debe ser un UUID válido.',
            'exists' => 'El :attribute seleccionado no es válido.',
        ],

        'name' => [
            'string' => 'El campo :attribute debe ser una cadena de texto.',
            'max' => 'El campo :attribute no puede tener más de :max caracteres.',
        ],

        'qualitative_evaluation_session_id' => [
            'required' => 'El campo :attribute es obligatorio.',
            'uuid' => 'El campo :attribute debe ser un UUID válido.',
            'exists' => 'El :attribute seleccionado no es válido.',
        ],

        'records' => [
            'required' => 'El campo :attribute es obligatorio.',
            'array' => 'El campo :attribute debe ser una lista.',
            'min' => 'El campo :attribute debe contener al menos un registro.',
        ],

        'records.*.student_id' => [
            'required' => 'El estudiante es obligatorio.',
            'uuid' => 'El estudiante debe ser un UUID válido.',
            'exists' => 'El estudiante seleccionado no es válido.',
        ],

        'records.*.skills' => [
            'required' => 'Las destrezas son obligatorias.',
            'array' => 'Las destrezas deben ser una lista.',
            'min' => 'Las destrezas deben contener al menos un registro.',
        ],

        'records.*.skills.*.qualitative_evaluation_component_id' => [
            'required' => 'La destreza es obligatoria.',
            'uuid' => 'La destreza debe ser un UUID válido.',
            'exists' => 'La destreza seleccionada no es válida.',
        ],

        'records.*.skills.*.value' => [
            'in' => 'El valor seleccionado no es válido.',
        ],

        'records.*.skills.*.observation' => [
            'string' => 'La observación debe ser una cadena de texto.',
            'max' => 'La observación no puede tener más de :max caracteres.',
        ],
    ],

    'attributes' => [
        'academic_year_id' => 'año académico',
        'evaluation_period_id' => 'período de evaluación',
        'course_id' => 'curso',
        'specialty_id' => 'especialidad',
        'parallel_id' => 'paralelo',
        'modality_id' => 'modalidad',
        'shift_id' => 'jornada',
        'subject_id' => 'asignatura',
        'name' => 'nombre',
        'qualitative_evaluation_session_id' => 'sesión de evaluación cualitativa',
        'records' => 'registros',
        'value' => 'valor',
        'observation' => 'observación',
    ],
];
