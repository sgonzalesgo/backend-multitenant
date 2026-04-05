<?php


return [
    'messages' => [
        'listed' => 'Cargos del tenant listados correctamente.',
        'retrieved' => 'Cargo del tenant obtenido correctamente.',
        'created' => 'Cargo del tenant creado correctamente.',
        'updated' => 'Cargo del tenant actualizado correctamente.',
        'not_found' => 'Cargo del tenant no encontrado.',
        'exception' => 'Ocurrió un error al procesar el cargo del tenant.',
    ],

    'audit' => [
        'created' => 'Cargo del tenant creado.',
        'updated' => 'Cargo del tenant actualizado.',
    ],

    'validation' => [
        'attributes' => [
            'tenant_id' => 'tenant',
            'person_id' => 'persona',
            'position_id' => 'cargo',
            'signature' => 'firma',
            'is_active' => 'estado',
            'start_date' => 'fecha de inicio',
            'end_date' => 'fecha de fin',
        ],

        'custom' => [
            'tenant_id.required' => 'El campo :attribute es obligatorio.',
            'tenant_id.uuid' => 'El campo :attribute debe ser un UUID válido.',
            'tenant_id.exists' => 'El :attribute seleccionado no es válido.',

            'person_id.required' => 'El campo :attribute es obligatorio.',
            'person_id.uuid' => 'El campo :attribute debe ser un UUID válido.',
            'person_id.exists' => 'La :attribute seleccionada no es válida.',

            'position_id.required' => 'El campo :attribute es obligatorio.',
            'position_id.uuid' => 'El campo :attribute debe ser un UUID válido.',
            'position_id.exists' => 'El :attribute seleccionado no es válido.',

            'signature.file' => 'El campo :attribute debe ser un archivo.',
            'signature.mimes' => 'El campo :attribute debe ser un archivo de tipo: jpg, jpeg, png, svg, webp.',
            'signature.max' => 'El campo :attribute no debe ser mayor a 2048 kilobytes.',

            'is_active.boolean' => 'El campo :attribute debe ser verdadero o falso.',

            'start_date.date' => 'El campo :attribute no es una fecha válida.',
            'end_date.date' => 'El campo :attribute no es una fecha válida.',
            'end_date.after_or_equal' => 'El campo :attribute debe ser una fecha posterior o igual a la fecha de inicio.',

            'person_position_unique' => 'Esta persona ya tiene asignado este cargo en el tenant seleccionado.',
        ],
    ],
];
