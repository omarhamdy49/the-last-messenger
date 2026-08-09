<?php

declare(strict_types=1);

use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Laravel\Sanctum\Http\Middleware\AuthenticateSession;
use Laravel\Sanctum\Sanctum;

return [
    'stateful' => array_filter(explode(',', (string) env(
        'SANCTUM_STATEFUL_DOMAINS',
        'localhost,localhost:3000,127.0.0.1,127.0.0.1:8000,::1'.Sanctum::currentApplicationUrlWithPort(),
    ))),

    'guard' => ['web'],

    // Personal access tokens expire after 30 days by default. The previous
    // SANCTUM_TOKEN_EXPIRATION value in .env.example was not connected to any
    // config file, so tokens were effectively non-expiring.
    'expiration' => (int) env('SANCTUM_TOKEN_EXPIRATION', 43200),

    // A recognizable prefix improves automated secret-leak detection.
    'token_prefix' => env('SANCTUM_TOKEN_PREFIX', 'tlm_'),

    'middleware' => [
        'authenticate_session' => AuthenticateSession::class,
        'encrypt_cookies' => EncryptCookies::class,
        'validate_csrf_token' => ValidateCsrfToken::class,
    ],
];
