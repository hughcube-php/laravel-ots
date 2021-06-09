<?php

use Illuminate\Support\Str;

return [

    'default' => env('CACHE_DRIVER', 'array'),

    'stores' => [
        'array' => [
            'driver' => 'array',
            'serialize' => false,
        ],

        'ots' => [
            'driver' => 'ots',
            'connection' => 'ots',
            'table' => 'cache',
        ],
    ],

    'prefix' => env('CACHE_PREFIX', Str::slug(env('APP_NAME', 'laravel'), '_') . '_cache'),

];
