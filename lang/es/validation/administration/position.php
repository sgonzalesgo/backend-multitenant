<?php

return [
    'messages' => [
        'listed' => 'Cargos listados correctamente.',
        'retrieved' => 'Cargo obtenido correctamente.',
        'created' => 'Cargo creado correctamente.',
        'updated' => 'Cargo actualizado correctamente.',
        'not_found' => 'Cargo no encontrado.',
        'exception' => 'Ocurrió un error al procesar el cargo.',
    ],

    'audit' => [
        'created' => 'Cargo creado.',
        'updated' => 'Cargo actualizado.',
    ],

    'validation' => [
        'attributes' => [
            'name' => 'nombre',
            'code' => 'código',
            'description' => 'descripción',
            'is_active' => 'estado',
        ],

        'custom' => [
            'name.required' => 'El campo :attribute es obligatorio.',
            'name.string' => 'El campo :attribute debe ser una cadena de texto.',
            'name.max' => 'El campo :attribute no debe ser mayor a 255 caracteres.',
            'name.unique' => 'Este :attribute ya está en uso.',

            'code.string' => 'El campo :attribute debe ser una cadena de texto.',
            'code.max' => 'El campo :attribute no debe ser mayor a 100 caracteres.',
            'code.unique' => 'Este :attribute ya está en uso.',

            'description.string' => 'El campo :attribute debe ser una cadena de texto.',

            'is_active.boolean' => 'El campo :attribute debe ser verdadero o falso.',
        ],
    ],
];
