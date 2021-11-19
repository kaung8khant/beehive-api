<?php

return [
    'kbz_pay' => [
        'merch_code' => env('KBZ_PAY_MERCH_CODE'),
        'app_id' => env('KBZ_PAY_APP_ID'),
        'app_key' => env('KBZ_PAY_APP_KEY'),
        'create_url' => env('KBZ_PAY_CREATE_URL'),
        'notify_url' => env('KBZ_PAY_NOTIFY_URL'),
    ],

    'cb_pay' => [
        'merch_id' => env('CB_PAY_MERCH_ID'),
        'sub_merch_id' => env('CB_PAY_SUB_MERCH_ID'),
        'terminal_id' => env('CB_PAY_TERMINAL_ID'),
        'generate_url' => env('CB_PAY_GENERATE_URL'),
        'transaction_url' => env('CB_PAY_TRANSACTION_URL'),
        'token' => env('CB_PAY_TOKEN'),

        'trans_status' => [
            'P' => 'Pending',
            'S' => 'Success',
            'E' => 'Expired',
            'C' => 'Cancelled',
            'L' => 'Over limit',
        ],
    ],
];
