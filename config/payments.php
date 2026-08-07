<?php

return [

    'providers' => [
        'iyzico' => [
            'label' => 'Iyzico',
            'enabled' => env('IYZICO_ENABLED', true),
            'supports_direct' => env('IYZICO_DIRECT', false),
            'supports_installments' => env('IYZICO_DIRECT', false),
        ],
        'stripe' => [
            'label' => 'Stripe',
            'enabled' => env('STRIPE_ENABLED', true),
            'supports_direct' => false,
            'supports_installments' => false,
        ],
    ],

];
