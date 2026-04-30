<?php


return [

    'attributes' => [
        // Person
        'full_name' => 'full name',
        'photo' => 'photo',
        'email' => 'email',
        'phone' => 'phone',
        'address' => 'address',
        'country_id' => 'country',
        'state_id' => 'state / province',
        'city_id' => 'city',
        'zip' => 'ZIP code',
        'legal_id' => 'legal ID',
        'legal_id_type' => 'legal ID type',
        'birthday' => 'birthday',
        'gender' => 'gender',
        'marital_status' => 'marital status',
        'blood_group' => 'blood group',
        'nationality' => 'nationality',
        'deceased_at' => 'deceased at',
        'status_changed_at' => 'status changed at',

        // Student
        'status' => 'status',
        'notes' => 'notes',

        // User
        'has_user' => 'user assignment',
        'user_name' => 'user name',
        'user_email' => 'user email',
        'user_password' => 'user password',
        'user_password_confirmation' => 'password confirmation',
        'user_status' => 'user status',
    ],

    'custom' => [
        // Person
        'full_name.required' => 'The full name is required.',
        'legal_id.required' => 'The legal ID is required.',
        'legal_id_type.required' => 'The legal ID type is required.',
        'email.email' => 'The email must be a valid email address.',

        // User
        'user_name.required_if' => 'The user name is required when assigning a user.',
        'user_email.required_if' => 'The user email is required when assigning a user.',
        'user_email.email' => 'The user email must be a valid email address.',
        'user_email.unique' => 'The user email has already been taken.',
        'user_password.required_if' => 'The user password is required when assigning a user.',
        'user_password.confirmed' => 'The password confirmation does not match.',
    ],

];
