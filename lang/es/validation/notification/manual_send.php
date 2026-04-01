<?php


return [
    'custom' => [
        'user_ids.required' => 'Debes seleccionar al menos un usuario.',
        'user_ids.array' => 'El campo usuarios debe ser una lista.',
        'user_ids.min' => 'Debes seleccionar al menos un usuario.',
        'user_ids.*.required' => 'Cada usuario es obligatorio.',
        'user_ids.*.uuid' => 'Cada usuario debe ser un UUID válido.',
        'user_ids.*.distinct' => 'No puedes repetir usuarios.',
        'user_ids.*.exists' => 'Uno o más usuarios no existen.',
        'title.required' => 'El título es obligatorio.',
        'title.string' => 'El título debe ser texto.',
        'title.max' => 'El título no puede superar :max caracteres.',
        'message.required' => 'El mensaje es obligatorio.',
        'message.string' => 'El mensaje debe ser texto.',
        'message.max' => 'El mensaje no puede superar :max caracteres.',
        'route.string' => 'La ruta debe ser texto.',
        'route.max' => 'La ruta no puede superar :max caracteres.',
        'payload.array' => 'El payload debe ser un objeto válido.',
    ],
    'attributes' => [
        'user_ids' => 'usuarios',
        'user_ids.*' => 'usuario',
        'title' => 'título',
        'message' => 'mensaje',
        'route' => 'ruta',
        'payload' => 'payload',
    ],
];
