<?php

return [

    'validation_sync' => [
        'attributes' => [
            'tenant_id' => 'tenant',
            'positions' => 'positions',
            'positions.*.id' => 'record',
            'positions.*.person_id' => 'person',
            'positions.*.position_id' => 'position',
            'positions.*.signature' => 'signature',
            'positions.*.order_to_sign' => 'sign order',
            'positions.*.is_active' => 'status',
            'positions.*.start_date' => 'start date',
            'positions.*.end_date' => 'end date',
        ],

        'custom' => [
            'tenant_id.required' => 'The tenant field is required.',
            'tenant_id.uuid' => 'The selected tenant is invalid.',
            'tenant_id.exists' => 'The selected tenant does not exist.',

            'positions.required' => 'You must send at least one position.',
            'positions.array' => 'The positions field must be an array.',

            'positions.*.id.uuid' => 'The record identifier is invalid.',
            'positions.*.id.exists' => 'The selected record does not exist.',

            'positions.*.person_id.required' => 'The person field is required.',
            'positions.*.person_id.uuid' => 'The selected person is invalid.',
            'positions.*.person_id.exists' => 'The selected person does not exist.',

            'positions.*.position_id.required' => 'The position field is required.',
            'positions.*.position_id.uuid' => 'The selected position is invalid.',
            'positions.*.position_id.exists' => 'The selected position does not exist.',

            'positions.*.signature.file' => 'The signature must be a valid file.',
            'positions.*.signature.mimes' => 'The signature must be a file of type: jpg, jpeg, png, svg, or webp.',
            'positions.*.signature.max' => 'The signature must not be greater than 2048 KB.',

            'positions.*.order_to_sign.required' => 'The sign order field is required.',
            'positions.*.order_to_sign.integer' => 'The sign order must be an integer.',
            'positions.*.order_to_sign.min' => 'The sign order must be at least 1.',

            'positions.*.is_active.boolean' => 'The status field must be true or false.',

            'positions.*.start_date.date' => 'The start date is not a valid date.',
            'positions.*.end_date.date' => 'The end date is not a valid date.',
            'positions.*.end_date.after_or_equal' => 'The end date must be equal to or later than the start date.',

            'person_position_unique' => 'The person and position combination already exists for this tenant.',
            'order_to_sign_unique' => 'The sign order cannot be repeated within the same tenant.',
            'invalid_position_for_tenant' => 'The submitted record does not belong to the selected tenant.',
            'end_date_after_or_equal' => 'The end date must be equal to or later than the start date.',
        ],
    ],

    'audit' => [
        'created' => 'Assignment created successfully.',
        'updated' => 'Assignment updated successfully.',
        'synced' => 'The tenant assignments were synchronized successfully.',
    ],

    'messages' => [
        'listed' => 'Records retrieved successfully.',
        'retrieved' => 'Record retrieved successfully.',
        'synced' => 'The tenant positions were synchronized successfully.',
        'exception' => 'An unexpected error occurred.',
    ],

];
