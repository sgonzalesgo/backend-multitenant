<?php


return [
    'messages' => [
        'listed' => 'Tenant positions listed successfully.',
        'retrieved' => 'Tenant position retrieved successfully.',
        'created' => 'Tenant position created successfully.',
        'updated' => 'Tenant position updated successfully.',
        'not_found' => 'Tenant position not found.',
        'exception' => 'An error occurred while processing the tenant position.',
    ],

    'audit' => [
        'created' => 'Tenant position created.',
        'updated' => 'Tenant position updated.',
    ],

    'validation' => [
        'attributes' => [
            'tenant_id' => 'tenant',
            'person_id' => 'person',
            'position_id' => 'position',
            'signature' => 'signature',
            'is_active' => 'status',
            'start_date' => 'start date',
            'end_date' => 'end date',
        ],

        'custom' => [
            'tenant_id.required' => 'The :attribute field is required.',
            'tenant_id.uuid' => 'The :attribute must be a valid UUID.',
            'tenant_id.exists' => 'The selected :attribute is invalid.',

            'person_id.required' => 'The :attribute field is required.',
            'person_id.uuid' => 'The :attribute must be a valid UUID.',
            'person_id.exists' => 'The selected :attribute is invalid.',

            'position_id.required' => 'The :attribute field is required.',
            'position_id.uuid' => 'The :attribute must be a valid UUID.',
            'position_id.exists' => 'The selected :attribute is invalid.',

            'signature.file' => 'The :attribute must be a file.',
            'signature.mimes' => 'The :attribute must be a file of type: jpg, jpeg, png, svg, webp.',
            'signature.max' => 'The :attribute may not be greater than 2048 kilobytes.',

            'is_active.boolean' => 'The :attribute field must be true or false.',

            'start_date.date' => 'The :attribute is not a valid date.',
            'end_date.date' => 'The :attribute is not a valid date.',
            'end_date.after_or_equal' => 'The :attribute must be a date after or equal to start date.',

            'person_position_unique' => 'This person already has this position assigned in the selected tenant.',
        ],
    ],
];
