<?php

return [

    'secret_key' => env('STRIPE_SECRET_KEY'),

    'publishable_key' => env('STRIPE_PUBLISHABLE_KEY'),

    'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),

    'currency' => env('STRIPE_CURRENCY', 'try'),

    'success_url' => env('STRIPE_SUCCESS_URL', env('FRONTEND_URL', 'http://localhost:3000').'/payment/result?status=success'),

    'cancel_url' => env('STRIPE_CANCEL_URL', env('FRONTEND_URL', 'http://localhost:3000').'/payment/result?status=failed'),

    'fake' => env('STRIPE_FAKE', false),

];
