<?php

declare(strict_types=1);

use Laravel\Telescope\Http\Middleware\Authorize;
use Laravel\Telescope\TelescopeApplicationServiceProvider;

return [
    /*
    |--------------------------------------------------------------------------
    | Telescope Master Switch
    |--------------------------------------------------------------------------
    |
    | This option may be used to disable all Telescope watchers regardless
    | of their individual configuration, which simply provides a single
    | and convenient way to enable or disable Telescope data storage.
    |
    */

    'enabled' => env('TELESCOPE_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Telescope Domain
    |--------------------------------------------------------------------------
    |
    | This is the subdomain where Telescope will be accessible from. If the
    | setting is null, Telescope will reside under the same domain as the
    | application. Otherwise, this value will serve as the subdomain.
    |
    */

    'domain' => env('TELESCOPE_DOMAIN', null),

    /*
    |--------------------------------------------------------------------------
    | Telescope Path
    |--------------------------------------------------------------------------
    |
    | This is the URI path where Telescope will be accessible from. Feel free
    | to change this path to anything you like. Note that the URI will not
    | affect the paths of its internal API that aren't exposed to users.
    |
    */

    'path' => env('TELESCOPE_PATH', 'telescope'),

    /*
    |--------------------------------------------------------------------------
    | Telescope Storage Driver
    |--------------------------------------------------------------------------
    |
    | This configuration options determines the storage driver that will
    | be used to store Telescope's data. In addition, you may set any
    | custom options as needed by the particular driver you choose.
    |
    */

    'driver' => env('TELESCOPE_DRIVER', 'database'),

    'storage' => [
        'database' => [
            'connection' => env('DB_CONNECTION', 'mysql'),
            'chunk' => 1000,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Telescope Queue
    |--------------------------------------------------------------------------
    |
    | This configuration options determines the queue connection and queue
    | which will be used to process Telescope jobs. You may leave these
    | values at their defaults if you are using Laravel's default queue.
    |
    */

    'queue' => [
        'connection' => env('TELESCOPE_QUEUE_CONNECTION', null),
        'queue' => env('TELESCOPE_QUEUE', null),
    ],

    /*
    |--------------------------------------------------------------------------
    | Telescope Route Middleware
    |--------------------------------------------------------------------------
    |
    | These middleware will be assigned to every Telescope route, giving you
    | the chance to add your own middleware to this list or change any of
    | the existing middleware. Or, you can simply stick with this list.
    |
    */

    'middleware' => [
        'web',
        Authorize::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Allowed / Ignored Paths & Commands
    |--------------------------------------------------------------------------
    |
    | The following array lists the URI paths and Artisan commands that will
    | not be watched by Telescope. In addition to this list, some Laravel
    | commands, like migrations and queue commands, are always ignored.
    |
    */

    'only_paths' => [
        // 'api/*',
    ],

    'ignore_paths' => [
        'livewire*',
        'nova-api*',
        'pulse*',
        'health',
        'up',
    ],

    'ignore_commands' => [
        'migrate',
        'migrate:*',
        'db:seed',
        'cache:clear',
        'cache:warm',
        'route:cache',
        'config:cache',
        'view:cache',
        'schedule:run',
    ],

    /*
    |--------------------------------------------------------------------------
    | Telescope Watchers
    |--------------------------------------------------------------------------
    |
    | The following array lists the "watchers" that will be registered with
    | Telescope. The watchers gather the application's profile data when
    | a request or task is executed. Feel free to customize this list.
    |
    */

    'watchers' => [
        \Laravel\Telescope\Watchers\BatchWatcher::class => env('TELESCOPE_BATCH_WATCHER', true),

        \Laravel\Telescope\Watchers\CacheWatcher::class => [
            'enabled' => env('TELESCOPE_CACHE_WATCHER', true),
            'ignored' => [
                'illuminate:queue:listener:*',
                'telescope:*',
                'pulse:*',
            ],
        ],

        \Laravel\Telescope\Watchers\ClientRequestWatcher::class => env('TELESCOPE_CLIENT_REQUEST_WATCHER', true),

        \Laravel\Telescope\Watchers\CommandWatcher::class => [
            'enabled' => env('TELESCOPE_COMMAND_WATCHER', true),
            'ignore' => [],
        ],

        \Laravel\Telescope\Watchers\DumpWatcher::class => [
            'enabled' => env('TELESCOPE_DUMP_WATCHER', true),
            'always' => env('TELESCOPE_DUMP_WATCHER_ALWAYS', false),
        ],

        \Laravel\Telescope\Watchers\EventWatcher::class => [
            'enabled' => env('TELESCOPE_EVENT_WATCHER', true),
            'ignore' => [],
        ],

        \Laravel\Telescope\Watchers\ExceptionWatcher::class => env('TELESCOPE_EXCEPTION_WATCHER', true),

        \Laravel\Telescope\Watchers\GateWatcher::class => [
            'enabled' => env('TELESCOPE_GATE_WATCHER', true),
            'ignore_abilities' => [],
            'ignore_packages' => true,
            'ignore_paths' => [],
        ],

        \Laravel\Telescope\Watchers\JobWatcher::class => env('TELESCOPE_JOB_WATCHER', true),

        \Laravel\Telescope\Watchers\LogWatcher::class => [
            'enabled' => env('TELESCOPE_LOG_WATCHER', true),
            'level' => 'debug',
        ],

        \Laravel\Telescope\Watchers\MailWatcher::class => env('TELESCOPE_MAIL_WATCHER', true),

        \Laravel\Telescope\Watchers\ModelWatcher::class => [
            'enabled' => env('TELESCOPE_MODEL_WATCHER', true),
            'events' => ['eloquent.*'],
            'hydrations' => true,
        ],

        \Laravel\Telescope\Watchers\NotificationWatcher::class => env('TELESCOPE_NOTIFICATION_WATCHER', true),

        \Laravel\Telescope\Watchers\QueryWatcher::class => [
            'enabled' => env('TELESCOPE_QUERY_WATCHER', true),
            'ignore_packages' => true,
            'ignore_paths' => [
                'telescope*',
                'nova*',
                'pulse*',
            ],
            'slow' => 100, // Slow query threshold in ms
        ],

        \Laravel\Telescope\Watchers\RedisWatcher::class => env('TELESCOPE_REDIS_WATCHER', true),

        \Laravel\Telescope\Watchers\RequestWatcher::class => [
            'enabled' => env('TELESCOPE_REQUEST_WATCHER', true),
            'size_limit' => env('TELESCOPE_RESPONSE_SIZE_LIMIT', 64),
            'ignore_http_methods' => [],
            'ignore_status_codes' => [204, 304, 404],
        ],

        \Laravel\Telescope\Watchers\ScheduleWatcher::class => env('TELESCOPE_SCHEDULE_WATCHER', true),
        \Laravel\Telescope\Watchers\ViewWatcher::class => env('TELESCOPE_VIEW_WATCHER', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | SIMRS Telescope Configuration
    |--------------------------------------------------------------------------
    |
    | Custom configuration for SIMRS-specific monitoring needs.
    |
    */

    'simrs' => [
        /**
         * Enable Telescope only in these environments.
         */
        'environments' => ['local', 'development', 'testing'],

        /**
         * Night mode settings for 24/7 monitoring.
         */
        'night_mode' => [
            'enabled' => env('TELESCOPE_NIGHT_MODE', false),
            'prune_after_hours' => 48,
            'record_slow_queries_only' => true,
            'slow_query_threshold' => 500,
        ],

        /**
         * Critical operations to always monitor.
         */
        'critical_operations' => [
            'App\Events\Broadcasting\*',
            'App\Jobs\Bpjs\*',
            'App\Jobs\SatuSehat\*',
            'App\Services\ReportService',
        ],
    ],
];
