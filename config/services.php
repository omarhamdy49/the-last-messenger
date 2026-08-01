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

    // ---------------------------------------------------------------------
    // "Ask Quran" LLM providers. Both speak the OpenAI chat-completions wire
    // format, so one client (OpenAiCompatChatClient) serves both; the active
    // one is picked by `quran_ai.provider` below. Keys are identical in shape
    // across providers — adding a third means adding a block here only.
    // `prices` is USD per 1M tokens, for the cost ledger / observability only
    // (not billing). Keep in sync with each provider's pricing page.
    // ---------------------------------------------------------------------

    // DeepSeek V4 Flash. The `deepseek-chat` alias routes to V4 Flash.
    'deepseek' => [
        'api_key' => env('DEEPSEEK_API_KEY'),
        'base_url' => env('DEEPSEEK_BASE_URL', 'https://api.deepseek.com'),
        'model' => env('DEEPSEEK_MODEL', 'deepseek-chat'),
        'timeout' => (int) env('DEEPSEEK_TIMEOUT', 40),
        'temperature' => (float) env('DEEPSEEK_TEMPERATURE', 0.2), // low — faithfulness over creativity
        'max_output_tokens' => (int) env('DEEPSEEK_MAX_OUTPUT', 1200),
        'prices' => ['input' => 0.14, 'cached_input' => 0.0028, 'output' => 0.28],
    ],

    // Google Gemini via its OpenAI-compatible endpoint (`/v1beta/openai`), which
    // supports tools, `tool_choice` and SSE streaming in the same shape DeepSeek
    // uses. Gemini 3.x requires the `extra_content.google.thought_signature` on a
    // replayed assistant tool-call message — the bridge echoes assistant messages
    // verbatim, which satisfies that; do not rebuild those messages by hand.
    // `prices` below are the PAID tier. Gemini's free tier is $0 for Flash, but
    // it is unusable here on two counts: gemini-3.6-flash allows only 20 requests
    // /day free (measured — one user question costs 3-7 requests through the tool
    // loop, so ~3 questions/day total), and Google states free-tier content "is
    // used to improve our products", i.e. user questions become training data.
    // The ledger reports the paid-tier cost so comparisons match the real bill.
    // ~10x DeepSeek on input and ~27x on output, and hidden thinking tokens bill
    // as output — `reasoning_effort` bounds them ('' leaves it to the model).
    'gemini' => [
        'api_key' => env('GEMINI_API_KEY'),
        'base_url' => env('GEMINI_BASE_URL', 'https://generativelanguage.googleapis.com/v1beta/openai'),
        'model' => env('GEMINI_MODEL', 'gemini-3.6-flash'),
        'timeout' => (int) env('GEMINI_TIMEOUT', 40),
        'temperature' => (float) env('GEMINI_TEMPERATURE', 0.2),
        'max_output_tokens' => (int) env('GEMINI_MAX_OUTPUT', 1200),
        'reasoning_effort' => env('GEMINI_REASONING_EFFORT', 'low'), // '' | low | medium | high
        'prices' => ['input' => 1.50, 'cached_input' => 0.15, 'output' => 7.50],
    ],

    // Gemini 2.5 Flash — the cheaper Gemini tier, kept as its own provider block
    // so `quran:ai-compare --providers=deepseek,gemini,gemini_25` prices each run
    // correctly. Shares GEMINI_API_KEY; 5x cheaper in / 3x cheaper out than 3.6.
    'gemini_25' => [
        'api_key' => env('GEMINI_API_KEY'),
        'base_url' => env('GEMINI_BASE_URL', 'https://generativelanguage.googleapis.com/v1beta/openai'),
        'model' => env('GEMINI_25_MODEL', 'gemini-2.5-flash'),
        'timeout' => (int) env('GEMINI_TIMEOUT', 40),
        'temperature' => (float) env('GEMINI_TEMPERATURE', 0.2),
        'max_output_tokens' => (int) env('GEMINI_MAX_OUTPUT', 1200),
        'reasoning_effort' => env('GEMINI_REASONING_EFFORT', 'low'),
        'prices' => ['input' => 0.30, 'cached_input' => 0.03, 'output' => 2.50],
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
        // Which LLM provider block above answers the feature; switching is an env
        // change + deploy, with instant rollback. `fallback_provider` takes over
        // for the rest of an inquiry when the primary is unreachable, 5xx's, or
        // (most often) hits a 429 — leave it empty to disable fallback entirely.
        // Each turn is costed at the prices of whichever provider served it.
        'provider' => env('QURAN_AI_PROVIDER', 'gemini'),
        'fallback_provider' => env('QURAN_AI_FALLBACK_PROVIDER', 'deepseek'),
        'monthly_quota' => (int) env('QURAN_AI_MONTHLY_QUOTA', 10),
        'min_chars' => (int) env('QURAN_AI_MIN_CHARS', 10),
        'max_chars' => (int) env('QURAN_AI_MAX_CHARS', 200),
        'answer_cache_days' => (int) env('QURAN_AI_ANSWER_CACHE_DAYS', 60),
        // Retention used by `quran:ai-prune` (scheduled) to bound disk growth.
        // history_retention_days = 0 keeps user history forever (default).
        'history_retention_days' => (int) env('QURAN_AI_HISTORY_RETENTION_DAYS', 0),
        'trashed_retention_days' => (int) env('QURAN_AI_TRASHED_RETENTION_DAYS', 30),
        'usage_retention_months' => (int) env('QURAN_AI_USAGE_RETENTION_MONTHS', 12),
    ],

];
