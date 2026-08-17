<?php

return [

    'name' => env('SHOP_NAME', 'GCPU'),

    'reservation_minutes' => (int) env('SHOP_RESERVATION_MINUTES', 15),

    'low_stock_threshold' => (int) env('SHOP_LOW_STOCK_THRESHOLD', 5),

];
