<?php

return [
    'wave' => [
        'graphql_endpoint' => env('WAVE_GRAPHQL_ENDPOINT'),
        'full_access_token' => env('WAVE_FULL_ACCESS_TOKEN'),
        'sales_tax_id' => env('SALES_TAX_ID'),
    ],

    'stripe' => [
        'public_key' => env('STRIPE_PUBLIC_KEY'),
        'secret_key' => env('STRIPE_SECRET_KEY'),
    ]
];
