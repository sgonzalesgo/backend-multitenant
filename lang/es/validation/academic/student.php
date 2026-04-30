<?php

return [

    'attributes' => [
        // Person
        'full_name' => 'nombre completo',
        'photo' => 'foto',
        'email' => 'correo electrónico',
        'phone' => 'teléfono',
        'address' => 'dirección',
        'country_id' => 'país',
        'state_id' => 'provincia / estado',
        'city_id' => 'ciudad',
        'zip' => 'código postal',
        'legal_id' => 'identificación',
        'legal_id_type' => 'tipo de identificación',
        'birthday' => 'fecha de nacimiento',
        'gender' => 'género',
        'marital_status' => 'estado civil',
        'blood_group' => 'grupo sanguíneo',
        'nationality' => 'nacionalidad',
        'deceased_at' => 'fecha de fallecimiento',
        'status_changed_at' => 'fecha de cambio de estado',

        // Student
        'status' => 'estado',
        'notes' => 'notas',

        // User
        'has_user' => 'asignación de usuario',
        'user_name' => 'nombre de usuario',
        'user_email' => 'correo del usuario',
        'user_password' => 'contraseña',
        'user_password_confirmation' => 'confirmación de contraseña',
        'user_status' => 'estado del usuario',
    ],

    'custom' => [
        // Person
        'full_name.required' => 'El nombre completo es obligatorio.',
        'legal_id.required' => 'La identificación es obligatoria.',
        'legal_id_type.required' => 'El tipo de identificación es obligatorio.',
        'email.email' => 'El correo electrónico debe ser válido.',

        // User
        'user_name.required_if' => 'El nombre de usuario es obligatorio al asignar un usuario.',
        'user_email.required_if' => 'El correo del usuario es obligatorio al asignar un usuario.',
        'user_email.email' => 'El correo del usuario debe ser válido.',
        'user_email.unique' => 'El correo del usuario ya está en uso.',
        'user_password.required_if' => 'La contraseña es obligatoria al asignar un usuario.',
        'user_password.confirmed' => 'La confirmación de la contraseña no coincide.',
    ],

];
