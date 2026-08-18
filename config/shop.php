<?php

return [

    'name' => env('SHOP_NAME', 'GCPU'),

    'reservation_minutes' => (int) env('SHOP_RESERVATION_MINUTES', 15),

    'low_stock_threshold' => (int) env('SHOP_LOW_STOCK_THRESHOLD', 5),

    'invoice' => [
        'legal_name' => env('SHOP_INVOICE_LEGAL_NAME', env('SHOP_NAME', 'GCPU')),
        'tax_office' => env('SHOP_INVOICE_TAX_OFFICE'),
        'tax_number' => env('SHOP_INVOICE_TAX_NUMBER'),
        'address' => env('SHOP_INVOICE_ADDRESS'),
        'email' => env('SHOP_INVOICE_EMAIL'),
    ],

];
