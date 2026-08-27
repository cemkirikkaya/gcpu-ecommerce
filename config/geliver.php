<?php

return [

    'api_token' => env('GELIVER_API_TOKEN'),

    'sender_address_id' => env('GELIVER_SENDER_ADDRESS_ID'),

    'fake' => env('GELIVER_FAKE', true),

    'test' => env('GELIVER_TEST', true),

    'auto_create_on_payment' => env('GELIVER_AUTO_CREATE_ON_PAYMENT', true),

    'sync_status_from_webhook' => env('GELIVER_SYNC_STATUS_FROM_WEBHOOK', true),

    'auto_sync_from_api' => env('GELIVER_AUTO_SYNC_FROM_API', true),

    'tracking_page_base' => env('GELIVER_TRACKING_PAGE_BASE', 'https://app.geliver.io/tracking'),

    'default_parcel' => [
        'weight' => env('GELIVER_DEFAULT_WEIGHT', '1.0'),
        'length' => env('GELIVER_DEFAULT_LENGTH', '30.0'),
        'width' => env('GELIVER_DEFAULT_WIDTH', '20.0'),
        'height' => env('GELIVER_DEFAULT_HEIGHT', '10.0'),
    ],

];
