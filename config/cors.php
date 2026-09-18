<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Cross-Origin Resource Sharing (CORS) for the public API
|--------------------------------------------------------------------------
|
| Browsers may call /api/* only from the origins listed in CORS_ALLOWED_ORIGINS
| (comma-separated, exact scheme://host[:port]). Each API client can narrow this
| further with its own allowed_origins, enforced by the api.client middleware.
|
| API clients authenticate with X-Api-Key / X-Api-Secret headers, not cookies, so
| credentials are not supported cross-origin.
|
| FRONTEND_URL and APP_URL stay allowed by default so existing local setups keep
| working when CORS_ALLOWED_ORIGINS is not set.
|
*/

$configured = array_values(array_filter(array_map('trim', explode(',', (string) env('CORS_ALLOWED_ORIGINS', '')))));

return [

    'paths' => ['api/*'],

    'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],

    'allowed_origins' => $configured !== [] ? $configured : array_values(array_filter([
        env('FRONTEND_URL', 'http://localhost:3000'),
        env('APP_URL', 'http://localhost:8000'),
    ])),

    'allowed_origins_patterns' => [],

    'allowed_headers' => [
        'Accept',
        'Accept-Language',
        'Authorization',
        'Content-Type',
        'X-Api-Key',
        'X-Api-Secret',
        'X-Request-Id',
        'X-Requested-With',
    ],

    'exposed_headers' => [
        'X-Request-Id',
        'X-RateLimit-Limit',
        'X-RateLimit-Remaining',
        'Retry-After',
    ],

    'max_age' => 86400,

    'supports_credentials' => false,

];
