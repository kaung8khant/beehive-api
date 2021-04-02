<?php

return [
    'dob' => [
        'model' => 'App\Models\Customer',
        'field' => 'id',
        'condition' => "=",
        'value' => 'auth',
    ],
    'new_customer_shop' => [
        'model' => 'App\Models\ShopOrder',
        'field' => 'customer_id',
        'condition' => "=",
        'value' => 'auth',
    ],
    'new_customer_restaurant' => [
        'model' => 'App\Models\RestaurantOrder',
        'field' => 'customer_id',
        'condition' => "=",
        'value' => 'auth',
    ],
];
