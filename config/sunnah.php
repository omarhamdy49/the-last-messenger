<?php

declare(strict_types=1);

return [
    'api' => [
        'key' => env('SUNNAH_API_KEY'),
        'base_url' => env('SUNNAH_API_BASE_URL', 'https://api.sunnah.com/v1/'),
        'timeout' => env('SUNNAH_API_TIMEOUT', 30),
        'retry_attempts' => env('SUNNAH_API_RETRY_ATTEMPTS', 3),
        'retry_delay' => env('SUNNAH_API_RETRY_DELAY', 1000), // milliseconds
        'rate_limit' => [
            'requests_per_minute' => env('SUNNAH_API_RATE_LIMIT', 60),
            'delay_between_requests' => env('SUNNAH_API_DELAY', 2000), // milliseconds (2 seconds)
            'delay_after_book' => env('SUNNAH_API_DELAY_AFTER_BOOK', 3000), // milliseconds (3 seconds after each book)
            'delay_on_429' => env('SUNNAH_API_DELAY_ON_429', 30000), // milliseconds (30 seconds when rate limited)
        ],
    ],

    'sync' => [
        'batch_size' => env('SUNNAH_SYNC_BATCH_SIZE', 500),
        'enable_notifications' => env('SUNNAH_SYNC_NOTIFICATIONS', true),
        'timezone' => env('APP_TIMEZONE', 'UTC'),
    ],
];
