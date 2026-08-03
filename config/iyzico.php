<?php

return [

    'api_key' => env('IYZICO_API_KEY'),

    'secret_key' => env('IYZICO_SECRET_KEY'),

    'base_url' => env('IYZICO_BASE_URL', 'https://sandbox-api.iyzipay.com'),

    'callback_url' => env('IYZICO_CALLBACK_URL', env('APP_URL').'/payment/iyzico/callback'),

    'frontend_result_url' => env('IYZICO_FRONTEND_RESULT_URL', env('FRONTEND_URL', 'http://localhost:3000').'/payment/result'),

    'fake' => env('IYZICO_FAKE', false),

];
