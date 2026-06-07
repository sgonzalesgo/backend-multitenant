<?php


return [
    'scale' => [
        'I' => [
            'label' => 'Iniciado',
            'numeric_value' => 1,
            'is_evaluable' => true,
        ],

        'EP' => [
            'label' => 'En Proceso',
            'numeric_value' => 2,
            'is_evaluable' => true,
        ],

        'A' => [
            'label' => 'Adquirido',
            'numeric_value' => 3,
            'is_evaluable' => true,
        ],

        'NE' => [
            'label' => 'No Evaluado',
            'numeric_value' => null,
            'is_evaluable' => false,
        ],
    ],

    'final_ranges' => [
        'I' => [
            'min' => 1.00,
            'max' => 1.49,
        ],

        'EP' => [
            'min' => 1.50,
            'max' => 2.49,
        ],

        'A' => [
            'min' => 2.50,
            'max' => 3.00,
        ],
    ],
];
