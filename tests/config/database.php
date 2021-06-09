<?php

return [

    'default'     => env('DB_CONNECTION', 'ots'),
    'connections' => [
        'ots' => [
            'driver'          => 'ots',
            'EndPoint'        => env('OTS_ENDPOINT'),
            'AccessKeyID'     => env('OTS_ACCESS_KEY_ID'),
            'AccessKeySecret' => env('OTS_ACCESS_KEY_SECRET'),
            'InstanceName'    => env('OTS_INSTANCE_NAME'),
        ],
    ],
    'migrations' => 'migrations',
];
