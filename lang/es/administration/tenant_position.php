<?php

return [

    'validation_sync' => [
        'attributes' => [
            'tenant_id' => 'tenant',
            'positions' => 'posiciones',
            'positions.*.id' => 'registro',
            'positions.*.person_id' => 'persona',
            'positions.*.position_id' => 'cargo',
            'positions.*.signature' => 'firma',
            'positions.*.order_to_sign' => 'orden de firma',
            'positions.*.is_active' => 'estado',
            'positions.*.start_date' => 'fecha de inicio',
            'positions.*.end_date' => 'fecha de finalización',
        ],

        'custom' => [
            'tenant_id.required' => 'El tenant es obligatorio.',
            'tenant_id.uuid' => 'El tenant seleccionado no es válido.',
            'tenant_id.exists' => 'El tenant seleccionado no existe.',

            'positions.required' => 'Debe enviar al menos una posición.',
            'positions.array' => 'El listado de posiciones debe ser un arreglo.',

            'positions.*.id.uuid' => 'El identificador del registro no es válido.',
            'positions.*.id.exists' => 'El registro seleccionado no existe.',

            'positions.*.person_id.required' => 'La persona es obligatoria.',
            'positions.*.person_id.uuid' => 'La persona seleccionada no es válida.',
            'positions.*.person_id.exists' => 'La persona seleccionada no existe.',

            'positions.*.position_id.required' => 'El cargo es obligatorio.',
            'positions.*.position_id.uuid' => 'El cargo seleccionado no es válido.',
            'positions.*.position_id.exists' => 'El cargo seleccionado no existe.',

            'positions.*.signature.file' => 'La firma debe ser un archivo válido.',
            'positions.*.signature.mimes' => 'La firma debe ser un archivo de tipo: jpg, jpeg, png, svg o webp.',
            'positions.*.signature.max' => 'La firma no debe superar los 2048 KB.',

            'positions.*.order_to_sign.required' => 'El orden de firma es obligatorio.',
            'positions.*.order_to_sign.integer' => 'El orden de firma debe ser un número entero.',
            'positions.*.order_to_sign.min' => 'El orden de firma debe ser mayor o igual a 1.',

            'positions.*.is_active.boolean' => 'El estado debe ser verdadero o falso.',

            'positions.*.start_date.date' => 'La fecha de inicio no es válida.',
            'positions.*.end_date.date' => 'La fecha de finalización no es válida.',
            'positions.*.end_date.after_or_equal' => 'La fecha de finalización debe ser igual o posterior a la fecha de inicio.',

            'person_position_unique' => 'La combinación de persona y cargo ya existe para este tenant.',
            'order_to_sign_unique' => 'El orden de firma no se puede repetir dentro del mismo tenant.',
            'invalid_position_for_tenant' => 'El registro enviado no pertenece al tenant seleccionado.',
            'end_date_after_or_equal' => 'La fecha de finalización debe ser igual o posterior a la fecha de inicio.',
        ],
    ],

    'audit' => [
        'created' => 'Asignación creada correctamente.',
        'updated' => 'Asignación actualizada correctamente.',
        'synced' => 'Las asignaciones del tenant fueron sincronizadas correctamente.',
    ],

    'messages' => [
        'listed' => 'Registros obtenidos correctamente.',
        'retrieved' => 'Registro obtenido correctamente.',
        'synced' => 'Las posiciones del tenant fueron sincronizadas correctamente.',
        'exception' => 'Ocurrió un error inesperado.',
    ],

];
