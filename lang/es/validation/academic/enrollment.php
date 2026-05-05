<?php

return [

    'attributes' => [
        'enrollment_code' => 'código de matrícula',
        'student_id' => 'estudiante',
        'academic_year_id' => 'año académico',
        'course_id' => 'curso',
        'parallel_id' => 'paralelo',
        'shift_id' => 'jornada',
        'enrollment_status_id' => 'estado de matrícula',
        'assigned_user_id' => 'usuario asignado',

        'is_new' => 'matrícula nueva',
        'is_conditional' => 'matrícula condicional',
        'is_active' => 'activo',

        'observation' => 'observación',

        'submitted_at' => 'fecha de envío',
    ],

    'custom' => [
        'student_id.required' => 'El estudiante es obligatorio.',
        'student_id.exists' => 'El estudiante seleccionado no es válido.',

        'academic_year_id.required' => 'El año académico es obligatorio.',
        'academic_year_id.exists' => 'El año académico seleccionado no es válido.',

        'course_id.exists' => 'El curso seleccionado no es válido.',
        'parallel_id.exists' => 'El paralelo seleccionado no es válido.',
        'shift_id.exists' => 'La jornada seleccionada no es válida.',
        'enrollment_status_id.exists' => 'El estado de matrícula seleccionado no es válido.',

        'assigned_user_id.exists' => 'El usuario seleccionado no es válido.',

        'observation.max' => 'La observación no puede tener más de 5000 caracteres.',
    ],

];
