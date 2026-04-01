<?php

return [
    'list' => [
        'custom' => [
            'per_page.integer' => 'El valor por página debe ser un número.',
            'per_page.min' => 'El valor por página debe ser al menos 1.',
            'per_page.max' => 'El valor por página no puede ser mayor que 100.',
            'sort_by.in' => 'El campo de orden seleccionado no es válido.',
            'sort_direction.in' => 'La dirección de orden seleccionada no es válida.',
        ],
        'attributes' => [
            'per_page' => 'por página',
            'q' => 'búsqueda',
            'is_active' => 'estado activo',
            'is_system' => 'estado del sistema',
            'category' => 'categoría',
            'paginate' => 'paginar',
            'sort_by' => 'ordenar por',
            'sort_direction' => 'dirección de orden',
        ],
    ],

    'store' => [
        'custom' => [
            'code.required' => 'El campo código es obligatorio.',
            'code.max' => 'El código no puede tener más de 100 caracteres.',
            'name.required' => 'El campo nombre es obligatorio.',
            'name.max' => 'El nombre no puede tener más de 150 caracteres.',
            'color.max' => 'El color no puede tener más de 20 caracteres.',
            'icon.max' => 'El icono no puede tener más de 100 caracteres.',
        ],
        'attributes' => [
            'code' => 'código',
            'name' => 'nombre',
            'description' => 'descripción',
            'color' => 'color',
            'icon' => 'icono',
            'is_active' => 'estado activo',
            'settings' => 'configuración',
            'settings.category' => 'categoría',
            'settings.requires_location' => 'requiere ubicación',
            'settings.requires_audience' => 'requiere audiencia',
            'settings.supports_attendance' => 'soporta asistencia',
            'settings.supports_response' => 'soporta respuesta',
            'settings.supports_recurrence' => 'soporta recurrencia',
            'settings.default_all_day' => 'todo el día por defecto',
        ],
    ],

    'update' => [
        'custom' => [
            'code.required' => 'El campo código es obligatorio.',
            'code.max' => 'El código no puede tener más de 100 caracteres.',
            'name.required' => 'El campo nombre es obligatorio.',
            'name.max' => 'El nombre no puede tener más de 150 caracteres.',
            'color.max' => 'El color no puede tener más de 20 caracteres.',
            'icon.max' => 'El icono no puede tener más de 100 caracteres.',
        ],
        'attributes' => [
            'code' => 'código',
            'name' => 'nombre',
            'description' => 'descripción',
            'color' => 'color',
            'icon' => 'icono',
            'is_active' => 'estado activo',
            'settings' => 'configuración',
            'settings.category' => 'categoría',
            'settings.requires_location' => 'requiere ubicación',
            'settings.requires_audience' => 'requiere audiencia',
            'settings.supports_attendance' => 'soporta asistencia',
            'settings.supports_response' => 'soporta respuesta',
            'settings.supports_recurrence' => 'soporta recurrencia',
            'settings.default_all_day' => 'todo el día por defecto',
        ],
    ],
];
