<?php

declare(strict_types=1);

use Illuminate\Support\Str;

return [
    /*
    |--------------------------------------------------------------------------
    | Default Cache Store
    |--------------------------------------------------------------------------
    |
    | This option controls the default cache connection that gets used while
    | using this caching library. This connection is used when another is
    | not explicitly specified when executing a given caching function.
    |
    */

    'default' => env('CACHE_STORE', 'redis'),

    /*
    |--------------------------------------------------------------------------
    | Cache Stores
    |--------------------------------------------------------------------------
    |
    | Here you may define all of the cache "stores" for your application as
    | well as their drivers. You may even define multiple stores for the
    | same cache driver to group types of items stored in your caches.
    |
    | Supported drivers: "apc", "array", "database", "file", "memcached",
    |                    "redis", "dynamodb", "octane", "null"
    |
    */

    'stores' => [

        'array' => [
            'driver' => 'array',
            'serialize' => false,
        ],

        'database' => [
            'driver' => 'database',
            'connection' => env('DB_CACHE_CONNECTION'),
            'table' => env('DB_CACHE_TABLE', 'cache'),
            'lock_connection' => env('DB_CACHE_LOCK_CONNECTION'),
            'lock_table' => env('DB_CACHE_LOCK_TABLE'),
        ],

        'file' => [
            'driver' => 'file',
            'path' => storage_path('framework/cache/data'),
            'lock_path' => storage_path('framework/cache/data'),
        ],

        'memcached' => [
            'driver' => 'memcached',
            'persistent_id' => env('MEMCACHED_PERSISTENT_ID'),
            'sasl' => [
                env('MEMCACHED_USERNAME'),
                env('MEMCACHED_PASSWORD'),
            ],
            'options' => [
                // Memcached::OPT_CONNECT_TIMEOUT => 2000,
            ],
            'servers' => [
                [
                    'host' => env('MEMCACHED_HOST', '127.0.0.1'),
                    'port' => env('MEMCACHED_PORT', 11211),
                    'weight' => 100,
                ],
            ],
        ],

        'redis' => [
            'driver' => 'redis',
            'connection' => env('REDIS_CACHE_CONNECTION', 'cache'),
            'lock_connection' => env('REDIS_CACHE_LOCK_CONNECTION', 'default'),
        ],

        /**
         * Redis Queue Store
         * Optimized for queue-related data with shorter TTL.
         */
        'redis:queue' => [
            'driver' => 'redis',
            'connection' => 'queue_cache',
            'lock_connection' => 'default',
        ],

        /**
         * Redis Statistics Store
         * Optimized for statistical data and indicators.
         */
        'redis:stats' => [
            'driver' => 'redis',
            'connection' => 'stats_cache',
            'lock_connection' => 'default',
        ],

        /**
         * File Views Store
         * For caching rendered views and fragments.
         */
        'file:views' => [
            'driver' => 'file',
            'path' => storage_path('framework/cache/views'),
        ],

        'dynamodb' => [
            'driver' => 'dynamodb',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
            'table' => env('DYNAMODB_CACHE_TABLE', 'cache'),
            'endpoint' => env('DYNAMODB_ENDPOINT'),
        ],

        'octane' => [
            'driver' => 'octane',
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Key Prefix
    |--------------------------------------------------------------------------
    |
    | When utilizing the APC, database, memcached, Redis, and DynamoDB cache
    | stores, there might be other applications using the same cache. So,
    | we'll specify a value to get prefixed to all our keys to avoid collision.
    |
    */

    'prefix' => env('CACHE_PREFIX', Str::slug(env('APP_NAME', 'rumahsakitku'), '_') . '_cache_'),

    /*
    |--------------------------------------------------------------------------
    | SIMRS Cache Configuration
    |--------------------------------------------------------------------------
    |
    | Custom configuration for SIMRS-specific caching needs.
    |
    */

    'simrs' => [
        /**
         * Default TTL values in seconds.
         */
        'ttl' => [
            'short' => 300,      // 5 minutes
            'medium' => 1800,    // 30 minutes
            'long' => 3600,      // 1 hour
            'extended' => 86400, // 24 hours
        ],

        /**
         * Cache tags for different data types.
         * Note: Requires Redis with tag support.
         */
        'tags' => [
            'patients' => 'simrs:patients',
            'visits' => 'simrs:visits',
            'queues' => 'simrs:queues',
            'rooms' => 'simrs:rooms',
            'medicines' => 'simrs:medicines',
            'reports' => 'simrs:reports',
            'indicators' => 'simrs:indicators',
        ],

        /**
         * Warming configuration.
         */
        'warming' => [
            'enabled' => env('CACHE_WARMING_ENABLED', true),
            'schedule' => env('CACHE_WARMING_SCHEDULE', '0 2 * * *'), // 2 AM daily
            'sections' => [
                'queues',
                'rooms',
                'medicines',
                'patients',
                'indicators',
                'visits',
            ],
        ],

        /**
         * Cache invalidation triggers.
         * Define which model events should clear which cache.
         */
        'invalidation' => [
            'patients' => ['created', 'updated', 'deleted'],
            'visits' => ['created', 'updated', 'deleted'],
            'queues' => ['created', 'updated'],
            'rooms' => ['updated'],
            'beds' => ['updated'],
            'medicines' => ['updated', 'deleted'],
        ],
    ],
];
