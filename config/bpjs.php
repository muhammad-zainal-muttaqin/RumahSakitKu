<?php

return [
    'mode' => env('BPJS_MODE', 'development'),

    'development' => [
        'base_url' => 'https://apijkn-dev.bpjs-kesehatan.go.id',
        'cons_id' => env('BPJS_CONS_ID'),
        'secret_key' => env('BPJS_SECRET_KEY'),
        'user_key' => env('BPJS_USER_KEY'),
    ],

    'production' => [
        'base_url' => env('BPJS_BASE_URL', 'https://apijkn.bpjs-kesehatan.go.id'),
        'cons_id' => env('BPJS_CONS_ID'),
        'secret_key' => env('BPJS_SECRET_KEY'),
        'user_key' => env('BPJS_USER_KEY'),
    ],

    'ppk' => [
        'code' => env('BPJS_PPK_CODE'),
        'name' => env('BPJS_PPK_NAME', 'RS RumahSakitKu'),
    ],

    'services' => [
        'vclaim' => '/vclaim-rest',
        'pcare' => '/pcare-rest',
        'eklaim' => '/eklaim-rest',
    ],

    'timeout' => 60,
    'retry_times' => 3,
    'retry_sleep' => 1000,
];
