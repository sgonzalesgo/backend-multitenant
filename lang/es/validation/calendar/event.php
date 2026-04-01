<?php

return [
    'list' => [
        'custom' => [
            'end.after_or_equal' => 'La fecha de fin debe ser una fecha posterior o igual a la fecha de inicio.',
            'event_type_id.uuid' => 'El tipo de evento no es válido.',
            'status.in' => 'El estado seleccionado no es válido.',
            'visibility.in' => 'La visibilidad seleccionada no es válida.',
        ],
        'attributes' => [
            'start' => 'fecha de inicio',
            'end' => 'fecha de fin',
            'event_type_id' => 'tipo de evento',
            'created_by_me' => 'creados por mí',
            'status' => 'estado',
            'visibility' => 'visibilidad',
            'search' => 'búsqueda',
        ],
    ],

    'store' => [
        'custom' => [
            'title.required' => 'El campo título es obligatorio.',
            'title.max' => 'El título no puede tener más de 255 caracteres.',
            'url.url' => 'La URL del evento debe ser una URL válida.',
            'start_at.required' => 'La fecha de inicio es obligatoria.',
            'end_at.after_or_equal' => 'La fecha de fin debe ser posterior o igual a la fecha de inicio.',
            'status.in' => 'El estado seleccionado no es válido.',
            'visibility.in' => 'La visibilidad seleccionada no es válida.',
            'editable_by.in' => 'El modo de edición seleccionado no es válido.',
        ],
        'attributes' => [
            'title' => 'título',
            'description' => 'descripción',
            'location' => 'ubicación',
            'url' => 'URL del evento',
            'start_at' => 'fecha de inicio',
            'end_at' => 'fecha de fin',
            'all_day' => 'todo el día',
            'timezone' => 'zona horaria',
            'status' => 'estado',
            'visibility' => 'visibilidad',
            'editable_by' => 'modo de edición',
            'color' => 'color',
            'participants' => 'participantes',
            'audiences' => 'audiencias',
        ],
    ],

    'update' => [
        'custom' => [
            'title.required' => 'El campo título es obligatorio.',
            'url.url' => 'La URL del evento debe ser una URL válida.',
            'end_at.after_or_equal' => 'La fecha de fin debe ser posterior o igual a la fecha de inicio.',
            'status.in' => 'El estado seleccionado no es válido.',
            'visibility.in' => 'La visibilidad seleccionada no es válida.',
            'editable_by.in' => 'El modo de edición seleccionado no es válido.',
        ],
        'attributes' => [
            'title' => 'título',
            'description' => 'descripción',
            'location' => 'ubicación',
            'url' => 'URL del evento',
            'start_at' => 'fecha de inicio',
            'end_at' => 'fecha de fin',
            'all_day' => 'todo el día',
            'timezone' => 'zona horaria',
            'status' => 'estado',
            'visibility' => 'visibilidad',
            'editable_by' => 'modo de edición',
            'color' => 'color',
            'participants' => 'participantes',
            'audiences' => 'audiencias',
        ],
    ],
];
