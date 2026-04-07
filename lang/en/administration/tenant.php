<?php

return [
    'messages' => [
        'listed' => 'Tenants listed successfully.',
        'retrieved' => 'Tenant retrieved successfully.',
        'created' => 'Tenant created successfully.',
        'updated' => 'Tenant updated successfully.',
        'not_found' => 'Tenant not found.',
        'exception' => 'An error occurred while processing the tenant.',
    ],

    'audit' => [
        'created' => 'Tenant created.',
        'updated' => 'Tenant updated.',
    ],

    'validation' => [
        'attributes' => [
            'name' => 'name',
            'domain' => 'domain',
            'logo' => 'logo',
            'campus_logo' => 'campus logo',
            'country_logo' => 'country logo',
            'address' => 'address',
            'phone' => 'phone',
            'email' => 'email',
            'legal_id' => 'legal ID',
            'legal_id_type' => 'legal ID type',
            'is_active' => 'status',
            'business_name' => 'business name',
            'campus_type' => 'campus type',
            'slogan' => 'slogan',
            'amie_code' => 'AMIE code',
            'city' => 'city',
            'state' => 'state',
            'country' => 'country',
            'country_logo_position_right' => 'country logo right position',
            'zip' => 'zip code',

            // NEW
            'authorities' => 'authorities',
            'authorities.*.id' => 'authority',
            'authorities.*.person_id' => 'person',
            'authorities.*.position_id' => 'position',
            'authorities.*.signature' => 'signature',
            'authorities.*.order_to_sign' => 'signing order',
            'authorities.*.is_active' => 'authority status',
            'authorities.*.start_date' => 'start date',
            'authorities.*.end_date' => 'end date',
        ],

        'custom' => [
            'name.required' => 'The :attribute field is required.',
            'name.string' => 'The :attribute must be a string.',
            'name.max' => 'The :attribute may not be greater than 255 characters.',
            'name.unique' => 'This :attribute is already in use.',

            'domain.required' => 'The :attribute field is required.',
            'domain.string' => 'The :attribute must be a string.',
            'domain.max' => 'The :attribute may not be greater than 255 characters.',
            'domain.unique' => 'This :attribute is already in use.',

            'logo.file' => 'The :attribute must be a file.',
            'logo.mimes' => 'The :attribute must be a file of type: jpg, jpeg, png, svg, webp.',
            'logo.max' => 'The :attribute may not be greater than 2048 kilobytes.',

            'campus_logo.file' => 'The :attribute must be a file.',
            'campus_logo.mimes' => 'The :attribute must be a file of type: jpg, jpeg, png, svg, webp.',
            'campus_logo.max' => 'The :attribute may not be greater than 2048 kilobytes.',

            'country_logo.file' => 'The :attribute must be a file.',
            'country_logo.mimes' => 'The :attribute must be a file of type: jpg, jpeg, png, svg, webp.',
            'country_logo.max' => 'The :attribute may not be greater than 2048 kilobytes.',

            'email.email' => 'The :attribute must be a valid email address.',

            'is_active.boolean' => 'The :attribute field must be true or false.',
            'country_logo_position_right.boolean' => 'The :attribute field must be true or false.',

            'authorities.array' => 'The :attribute field must be a valid list.',

            'authorities.*.id.uuid' => 'The :attribute must be a valid UUID.',
            'authorities.*.id.exists' => 'The selected :attribute is invalid.',

            'authorities.*.person_id.required_with' => 'The :attribute field is required.',
            'authorities.*.person_id.uuid' => 'The :attribute must be a valid UUID.',
            'authorities.*.person_id.exists' => 'The selected :attribute is invalid.',

            'authorities.*.position_id.required_with' => 'The :attribute field is required.',
            'authorities.*.position_id.uuid' => 'The :attribute must be a valid UUID.',
            'authorities.*.position_id.exists' => 'The selected :attribute is invalid.',

            'authorities.*.signature.file' => 'The :attribute must be a file.',
            'authorities.*.signature.mimes' => 'The :attribute must be a file of type: jpg, jpeg, png, svg, webp.',
            'authorities.*.signature.max' => 'The :attribute may not be greater than 2048 kilobytes.',

            'authorities.*.order_to_sign.required_with' => 'The :attribute field is required.',
            'authorities.*.order_to_sign.integer' => 'The :attribute must be an integer.',
            'authorities.*.order_to_sign.min' => 'The :attribute must be at least 1.',

            'authorities.*.is_active.boolean' => 'The :attribute field must be true or false.',
            'authorities.*.start_date.date' => 'The :attribute is not a valid date.',
            'authorities.*.end_date.date' => 'The :attribute is not a valid date.',

            // NEW MESSAGES
            'authorities_end_date_after_or_equal' => 'The end date must be greater than or equal to the start date.',
            'authorities_duplicate_in_request' => 'You cannot repeat the same person and position combination.',
            'authorities_duplicate_in_tenant' => 'This person and position combination already exists for this tenant.',
            'order_to_sign_unique' => 'The signing order must be unique.',
            'invalid_position_for_tenant' => 'The authority does not belong to this tenant.',
        ],
    ],
];
