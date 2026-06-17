<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    // Social login (Laravel Socialite). A provider whose client_id is empty
    // is treated as "not configured": its button is hidden on the auth pages
    // and its routes redirect back to login with a friendly message.
    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT', '/auth/google/callback'),
    ],

    'facebook' => [
        'client_id' => env('FACEBOOK_CLIENT_ID'),
        'client_secret' => env('FACEBOOK_CLIENT_SECRET'),
        'redirect' => env('FACEBOOK_REDIRECT', '/auth/facebook/callback'),
    ],

    'quran' => [
        'auth_url' => env('QURAN_AUTH_API_URL', 'https://oauth2.quran.foundation/oauth2/token'),
        'client_id' => env('QURAN_API_KEY'),
        'client_secret' => env('QURAN_API_SECRET'),
        'api_url' => env('QURAN_API_BASE_URL', 'https://apis.quran.foundation/content/api/v4/'),

        // Authless public canonical endpoint (api.quran.com) — the same source the
        // quran.ai MCP wraps. Used by the public "Ask the Quran" inquiry search,
        // which needs no OAuth and no LLM.
        'public_api_url' => env('QURAN_PUBLIC_API_URL', 'https://api.quran.com/api/v4/'),
    ],

    // DeepSeek V4 Flash — answers the registered-user "Ask Quran" AI feature.
    // Chosen for cost: ~$0.14/M in (cached input ~$0.0028/M), ~$0.28/M out.
    // The `deepseek-chat` alias routes to V4 Flash.
    'deepseek' => [
        'api_key' => env('DEEPSEEK_API_KEY'),
        'base_url' => env('DEEPSEEK_BASE_URL', 'https://api.deepseek.com'),
        'model' => env('DEEPSEEK_MODEL', 'deepseek-chat'),
        'timeout' => (int) env('DEEPSEEK_TIMEOUT', 40),
        'temperature' => (float) env('DEEPSEEK_TEMPERATURE', 0.2), // low — faithfulness over creativity
        'max_output_tokens' => (int) env('DEEPSEEK_MAX_OUTPUT', 1200),
    ],

    // quran.ai MCP — grounds the AI answer in canonical text/translation/tafsir.
    // streamable-http, no auth. `default_tafsir`/`max_verses`/`allowed_tafsir`/
    // `max_tool_calls` are the cost guardrails (tafsir is the dominant token cost,
    // varying ~40x by edition). Allowlist blocks the heavy classical editions.
    'quran_mcp' => [
        'url' => env('QURAN_MCP_URL', 'https://mcp.quran.ai'),
        'timeout' => (int) env('QURAN_MCP_TIMEOUT', 10),
        'default_tafsir' => env('QURAN_MCP_TAFSIR', 'en-maarif-ul-quran'),
        'allowed_tafsir' => array_filter(explode(',', (string) env(
            'QURAN_MCP_ALLOWED_TAFSIR',
            'en-maarif-ul-quran,en-tazkirul-quran,en-ibn-kathir'
        ))),
        'max_verses' => (int) env('QURAN_MCP_MAX_VERSES', 6),
        'max_tool_calls' => (int) env('QURAN_MCP_MAX_TOOL_CALLS', 4),
    ],

    // "Ask Quran" AI access policy.
    'quran_ai' => [
        'monthly_quota' => (int) env('QURAN_AI_MONTHLY_QUOTA', 10),
        'min_chars' => (int) env('QURAN_AI_MIN_CHARS', 10),
        'max_chars' => (int) env('QURAN_AI_MAX_CHARS', 200),
        'answer_cache_days' => (int) env('QURAN_AI_ANSWER_CACHE_DAYS', 60),
    ],

];
