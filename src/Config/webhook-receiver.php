<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Route Prefix
    |--------------------------------------------------------------------------
    |
    | The prefix for all webhook receiver routes.
    |
    */
    'route_prefix' => env('WEBHOOK_RECEIVER_PREFIX', 'webhook'),

    /*
    |--------------------------------------------------------------------------
    | Deduplication Settings
    |--------------------------------------------------------------------------
    |
    | Settings for log deduplication to prevent duplicate entries.
    |
    */
    'deduplication' => [
        'enabled' => env('WEBHOOK_RECEIVER_DEDUP', true),
        'window_minutes' => env('WEBHOOK_RECEIVER_DEDUP_WINDOW', 5),
    ],

    /*
    |--------------------------------------------------------------------------
    | Retention Settings
    |--------------------------------------------------------------------------
    |
    | How long to keep logs before automatic cleanup.
    |
    */
    'retention_days' => env('WEBHOOK_RECEIVER_RETENTION_DAYS', 7),

    /*
    |--------------------------------------------------------------------------
    | Viewer Authentication
    |--------------------------------------------------------------------------
    |
    | Simple username/password authentication for the web viewer.
    |
    */
    'viewer' => [
        'username' => env('WEBHOOK_VIEWER_USERNAME', 'admin'),
        'password' => env('WEBHOOK_VIEWER_PASSWORD'),
    ],
];
