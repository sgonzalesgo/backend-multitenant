<?php

return [

    'attributes' => [
        'academic_year_id' => 'año académico',
        'course_id' => 'curso',
        'parallel_id' => 'paralelo',
        'modality_id' => 'modalidad',
        'shift_id' => 'jornada',

        'status' => 'estado',
        'general_observation' => 'observación general',

        'check_conflicts' => 'verificar conflictos',

        'frequencies' => 'frecuencias',

        'frequencies.*.day_of_week' => 'día de la semana',
        'frequencies.*.start_time' => 'hora de inicio',
        'frequencies.*.end_time' => 'hora de fin',
        'frequencies.*.classroom_id' => 'aula',
        'frequencies.*.subject_id' => 'asignatura',
        'frequencies.*.instructor_id' => 'instructor',
        'frequencies.*.observation' => 'observación',
    ],

    'custom' => [
        'academic_year_id.required' => 'El año académico es obligatorio.',
        'course_id.required' => 'El curso es obligatorio.',
        'parallel_id.required' => 'El paralelo es obligatorio.',
        'modality_id.required' => 'La modalidad es obligatoria.',
        'shift_id.required' => 'La jornada es obligatoria.',

        'frequencies.required' => 'Debe registrar al menos una frecuencia.',
        'frequencies.array' => 'Las frecuencias deben enviarse como una lista válida.',
        'frequencies.min' => 'Debe registrar al menos una frecuencia.',

        'frequencies.*.day_of_week.required' => 'El día de la semana es obligatorio.',
        'frequencies.*.start_time.required' => 'La hora de inicio es obligatoria.',
        'frequencies.*.end_time.required' => 'La hora de fin es obligatoria.',

        'frequencies.*.classroom_id.required' => 'El aula es obligatoria.',
        'frequencies.*.subject_id.required' => 'La asignatura es obligatoria.',
        'frequencies.*.instructor_id.required' => 'El instructor es obligatorio.',
    ],

];
