<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Allowed crawlers
    |--------------------------------------------------------------------------
    |
    | robots.txt is an allow-list here: these agents get access, `User-agent: *`
    | is denied everything. Two things to be clear about:
    |
    |  1. robots.txt is ADVISORY. Well-behaved crawlers honour it; scrapers and
    |     content thieves simply ignore it. It is not a security control, and it
    |     is not rate limiting. nginx's limit_req/limit_conn and Cloudflare's bot
    |     rules are what actually stop abusive traffic.
    |  2. Denying `*` also denies crawlers not yet listed, including new or
    |     regional search engines. That is the point of an allow-list, but it
    |     means this file needs occasional review or the site slowly drops out of
    |     indexes that did not exist when it was written.
    |
    */

    'search_engines' => [
        'Googlebot',            // Google — also covers Googlebot-Image/News/Video
        'Googlebot-Image',
        'Googlebot-News',
        'Googlebot-Video',
        'Bingbot',              // Microsoft Bing (and, via Bing's index, Copilot)
        'Slurp',                // Yahoo
        'DuckDuckBot',          // DuckDuckGo
        'Baiduspider',          // Baidu
        'YandexBot',            // Yandex
        'Applebot',             // Apple — Siri and Spotlight
        'Qwantify',             // Qwant
        'SeznamBot',            // Seznam (Czech)
        'Naver',                // Naver (Korean)
    ],

    /*
    | AI crawlers and assistants. Split from search engines on purpose: this is
    | the list to revisit if the position on AI training ever changes, without
    | touching search indexing.
    |
    | Note the distinction some vendors draw:
    |   - Google-Extended  → Gemini training (does NOT affect Search ranking)
    |   - GPTBot           → OpenAI model training
    |   - OAI-SearchBot    → ChatGPT search results
    |   - ChatGPT-User     → a user asking ChatGPT to fetch a page live
    |   - ClaudeBot        → Anthropic crawling
    |   - Claude-User      → a user asking Claude to fetch a page live
    */
    'ai_agents' => [
        'GPTBot',
        'OAI-SearchBot',
        'ChatGPT-User',
        'ClaudeBot',
        'Claude-User',
        'Claude-SearchBot',
        'anthropic-ai',
        'Google-Extended',
        'PerplexityBot',
        'Perplexity-User',
        'Applebot-Extended',
        'Meta-ExternalAgent',
        'CCBot',                // Common Crawl — feeds many open datasets
        'cohere-ai',
        'MistralAI-User',
        'DeepSeekBot',
        'YouBot',
    ],

    /*
    |--------------------------------------------------------------------------
    | Paths closed to every crawler, allowed ones included
    |--------------------------------------------------------------------------
    |
    | Admin, auth and per-user surfaces. These are already access-controlled —
    | listing them here keeps them out of indexes and out of crawl budget, it is
    | not what protects them.
    |
    */
    'disallowed_paths' => [
        '/su/portal/',      // admin panel
        '/api/',            // bearer-token API; nothing here is useful to a crawler
        '/login',
        '/register',
        '/profile',
        '/favorites',
        '/email/verify',
        '/auth/',           // social OAuth redirect/callback
        // Faceted search is an effectively infinite URL space with no index value. Written
        // without a `?` on purpose: robots.txt path matching is prefix-based and parsers
        // disagree about whether the query string participates, so `/search` reliably
        // covers both the bare page and every facet permutation.
        '/search',
        '/quran/ask',       // per-user AI inquiry surface
    ],

];
