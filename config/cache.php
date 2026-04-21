<?php

$stores = [
    'array' => [
        'driver' => 'array',
        'serialize' => false,
    ],
    'database' => [
        'driver' => 'database',
        'connection' => null,
        'table' => 'cache',
    ],
    'file' => [
        'driver' => 'file',
        'path' => storage_path('framework/cache/data'),
    ],
    'redis' => [
        'driver' => 'redis',
        'connection' => 'cache',
        'lock_connection' => 'default',
    ],
];

$memcachedClass = 'Memcached';

if (class_exists($memcachedClass)) {
    $stores['memcached'] = [
        'driver' => 'memcached',
        'persistent_id' => env('MEMCACHED_PERSISTENT_ID'),
        'sasl' => [
            env('MEMCACHED_USERNAME'),
            env('MEMCACHED_PASSWORD'),
        ],
        'options' => [
            constant($memcachedClass.'::OPT_CONNECT_TIMEOUT') => 2000,
        ],
        'servers' => [
            [
                'host' => env('MEMCACHED_HOST', '127.0.0.1'),
                'port' => env('MEMCACHED_PORT', 11211),
                'weight' => 100,
            ],
        ],
    ];
}

return [
    'default' => env('CACHE_DRIVER', 'file'),
    'stores' => $stores,
    'prefix' => env('CACHE_PREFIX', 'laravel_cache'),
];
