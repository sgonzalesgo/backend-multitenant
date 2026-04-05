<?php

return [
    'messages' => [
        'listed' => 'Tenants listados correctamente.',
        'retrieved' => 'Tenant obtenido correctamente.',
        'created' => 'Tenant creado correctamente.',
        'updated' => 'Tenant actualizado correctamente.',
        'not_found' => 'Tenant no encontrado.',
        'exception' => 'Ocurrió un error al procesar el tenant.',
    ],

    'audit' => [
        'created' => 'Tenant creado.',
        'updated' => 'Tenant actualizado.',
    ],

    'validation' => [
        'attributes' => [
            'name' => 'nombre',
            'domain' => 'dominio',
            'logo' => 'logo',
            'campus_logo' => 'logo del plantel',
            'country_logo' => 'logo del país',
            'address' => 'dirección',
            'phone' => 'teléfono',
            'email' => 'correo electrónico',
            'legal_id' => 'identificación legal',
            'legal_id_type' => 'tipo de identificación legal',
            'is_active' => 'estado',
            'business_name' => 'razón social',
            'campus_type' => 'tipo de plantel',
            'slogan' => 'eslogan',
            'amie_code' => 'código AMIE',
            'city' => 'ciudad',
            'state' => 'provincia/estado',
            'country' => 'país',
            'country_logo_position_right' => 'posición del logo del país a la derecha',
            'zip' => 'código postal',

            'authorities' => 'autoridades',
            'authorities.*.person_id' => 'persona',
            'authorities.*.position_id' => 'cargo',
            'authorities.*.signature' => 'firma',
            'authorities.*.is_active' => 'estado de la autoridad',
            'authorities.*.start_date' => 'fecha de inicio',
            'authorities.*.end_date' => 'fecha de fin',
        ],

        'custom' => [
            'name.required' => 'El campo :attribute es obligatorio.',
            'name.string' => 'El campo :attribute debe ser una cadena de texto.',
            'name.max' => 'El campo :attribute no debe ser mayor a 255 caracteres.',
            'name.unique' => 'Este :attribute ya está en uso.',

            'domain.required' => 'El campo :attribute es obligatorio.',
            'domain.string' => 'El campo :attribute debe ser una cadena de texto.',
            'domain.max' => 'El campo :attribute no debe ser mayor a 255 caracteres.',
            'domain.unique' => 'Este :attribute ya está en uso.',

            'logo.file' => 'El campo :attribute debe ser un archivo.',
            'logo.mimes' => 'El campo :attribute debe ser un archivo de tipo: jpg, jpeg, png, svg, webp.',
            'logo.max' => 'El campo :attribute no debe ser mayor a 2048 kilobytes.',

            'campus_logo.file' => 'El campo :attribute debe ser un archivo.',
            'campus_logo.mimes' => 'El campo :attribute debe ser un archivo de tipo: jpg, jpeg, png, svg, webp.',
            'campus_logo.max' => 'El campo :attribute no debe ser mayor a 2048 kilobytes.',

            'country_logo.file' => 'El campo :attribute debe ser un archivo.',
            'country_logo.mimes' => 'El campo :attribute debe ser un archivo de tipo: jpg, jpeg, png, svg, webp.',
            'country_logo.max' => 'El campo :attribute no debe ser mayor a 2048 kilobytes.',

            'email.email' => 'El campo :attribute debe ser un correo electrónico válido.',

            'is_active.boolean' => 'El campo :attribute debe ser verdadero o falso.',
            'country_logo_position_right.boolean' => 'El campo :attribute debe ser verdadero o falso.',

            'authorities.array' => 'El campo :attribute debe ser una lista válida.',

            'authorities.*.person_id.required_with' => 'El campo :attribute es obligatorio.',
            'authorities.*.person_id.uuid' => 'El campo :attribute debe ser un UUID válido.',
            'authorities.*.person_id.exists' => 'La :attribute seleccionada no es válida.',

            'authorities.*.position_id.required_with' => 'El campo :attribute es obligatorio.',
            'authorities.*.position_id.uuid' => 'El campo :attribute debe ser un UUID válido.',
            'authorities.*.position_id.exists' => 'El :attribute seleccionado no es válido.',

            'authorities.*.signature.file' => 'El campo :attribute debe ser un archivo.',
            'authorities.*.signature.mimes' => 'El campo :attribute debe ser un archivo de tipo: jpg, jpeg, png, svg, webp.',
            'authorities.*.signature.max' => 'El campo :attribute no debe ser mayor a 2048 kilobytes.',

            'authorities.*.is_active.boolean' => 'El campo :attribute debe ser verdadero o falso.',
            'authorities.*.start_date.date' => 'El campo :attribute no es una fecha válida.',
            'authorities.*.end_date.date' => 'El campo :attribute no es una fecha válida.',

            'authorities_end_date_after_or_equal' => 'La fecha de fin de la autoridad debe ser posterior o igual a la fecha de inicio.',
            'authorities_duplicate_in_request' => 'No puedes repetir la misma combinación de persona y cargo en la misma solicitud.',
            'authorities_duplicate_in_tenant' => 'Esta persona ya tiene asignado ese cargo en este tenant.',
        ],
    ],
];
