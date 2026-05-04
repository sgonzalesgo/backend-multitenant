<?php

return [

    'attributes' => [
        'educational_level_id' => 'nivel educativo',
        'instructor_id' => 'instructor',
        'code' => 'código',
        'name' => 'nombre',
        'description' => 'descripción',
        'capacity' => 'capacidad',
        'credits' => 'créditos',
        'theoretical_hours' => 'horas teóricas',
        'practical_hours' => 'horas prácticas',
        'total_hours' => 'horas totales',
        'status' => 'estado',
        'notes' => 'notas',
    ],

    'custom' => [
        'educational_level_id.required' => 'El nivel educativo es obligatorio.',
        'educational_level_id.exists' => 'El nivel educativo seleccionado no es válido.',

        'instructor_id.required' => 'El instructor es obligatorio.',
        'instructor_id.exists' => 'El instructor seleccionado no es válido.',

        'code.required' => 'El código es obligatorio.',
        'code.max' => 'El código no puede tener más de 50 caracteres.',

        'name.required' => 'El nombre es obligatorio.',

        'capacity.required' => 'La capacidad es obligatoria.',
        'capacity.integer' => 'La capacidad debe ser un número.',
        'capacity.min' => 'La capacidad debe ser mayor o igual a 0.',

        'credits.integer' => 'Los créditos deben ser un número.',
        'theoretical_hours.integer' => 'Las horas teóricas deben ser un número.',
        'practical_hours.integer' => 'Las horas prácticas deben ser un número.',
        'total_hours.integer' => 'Las horas totales deben ser un número.',
    ],

];
