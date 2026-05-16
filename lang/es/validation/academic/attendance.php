<?php


return [

    'custom' => [

        'academic_year_id' => [
            'required' => 'El campo :attribute es obligatorio.',
            'uuid' => 'El campo :attribute debe ser un UUID válido.',
            'exists' => 'El :attribute seleccionado no es válido.',
        ],

        'course_id' => [
            'required' => 'El campo :attribute es obligatorio.',
            'uuid' => 'El campo :attribute debe ser un UUID válido.',
            'exists' => 'El :attribute seleccionado no es válido.',
        ],

        'parallel_id' => [
            'required' => 'El campo :attribute es obligatorio.',
            'uuid' => 'El campo :attribute debe ser un UUID válido.',
            'exists' => 'El :attribute seleccionado no es válido.',
        ],

        'subject_id' => [
            'required' => 'El campo :attribute es obligatorio.',
            'uuid' => 'El campo :attribute debe ser un UUID válido.',
            'exists' => 'El :attribute seleccionado no es válido.',
        ],

        'attendance_date' => [
            'required' => 'El campo :attribute es obligatorio.',
            'date' => 'El campo :attribute debe ser una fecha válida.',
        ],

        'observation' => [
            'string' => 'El campo :attribute debe ser una cadena de texto.',
            'max' => 'El campo :attribute no debe ser mayor que :max caracteres.',
        ],

        'close' => [
            'boolean' => 'El campo :attribute debe ser verdadero o falso.',
        ],

        'records' => [
            'required' => 'El campo :attribute es obligatorio.',
            'array' => 'El campo :attribute debe ser una lista válida.',
            'min' => 'Debe existir al menos un registro de asistencia.',
        ],

        'records.*.id' => [
            'uuid' => 'El campo :attribute debe ser un UUID válido.',
            'exists' => 'El :attribute seleccionado no es válido.',
        ],

        'records.*.enrollment_id' => [
            'required' => 'El campo :attribute es obligatorio.',
            'uuid' => 'El campo :attribute debe ser un UUID válido.',
            'exists' => 'El :attribute seleccionado no es válido.',
        ],

        'records.*.student_id' => [
            'required' => 'El campo :attribute es obligatorio.',
            'uuid' => 'El campo :attribute debe ser un UUID válido.',
            'exists' => 'El :attribute seleccionado no es válido.',
        ],

        'records.*.person_id' => [
            'required' => 'El campo :attribute es obligatorio.',
            'uuid' => 'El campo :attribute debe ser un UUID válido.',
            'exists' => 'El :attribute seleccionado no es válido.',
        ],

        'records.*.status' => [
            'required' => 'El campo :attribute es obligatorio.',
            'in' => 'El :attribute seleccionado no es válido.',
        ],

        'records.*.late_minutes' => [
            'integer' => 'El campo :attribute debe ser numérico.',
            'min' => 'El campo :attribute debe ser al menos :min.',
            'max' => 'El campo :attribute no debe ser mayor que :max.',
        ],

        'records.*.observation' => [
            'string' => 'El campo :attribute debe ser una cadena de texto.',
            'max' => 'El campo :attribute no debe ser mayor que :max caracteres.',
        ],
    ],

    'attributes' => [
        'academic_year_id' => 'año académico',
        'course_id' => 'curso',
        'parallel_id' => 'paralelo',
        'subject_id' => 'asignatura',
        'attendance_date' => 'fecha de asistencia',

        'observation' => 'observación',
        'close' => 'cerrar asistencia',

        'records' => 'registros de asistencia',

        'records.*.id' => 'registro de asistencia',
        'records.*.enrollment_id' => 'matrícula',
        'records.*.student_id' => 'estudiante',
        'records.*.person_id' => 'persona',
        'records.*.status' => 'estado de asistencia',
        'records.*.late_minutes' => 'minutos de retraso',
        'records.*.observation' => 'observación',
    ],
];
