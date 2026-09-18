<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Quran module
|--------------------------------------------------------------------------
|
| All Quran content is imported from the Quran Foundation (QF) Content API v4
| and served from our own database. QF is only called by the importers, the
| sync command and the admin reciter-source actions — never by public routes.
|
| Credentials reuse the env names the module has always used
| (QURAN_API_KEY / QURAN_API_SECRET). They are read here and nowhere else, and
| must never be logged. See specs/006-quran-qf-refactor/contracts/qf-import.md.
|
*/

return [

    'qf' => [
        // prelive has only surahs 1–2; production is the full dataset.
        'env' => env('QF_ENV', 'production'),
        'auth_url' => env('QURAN_AUTH_API_URL', 'https://oauth2.quran.foundation/oauth2/token'),
        'api_url' => env('QURAN_API_BASE_URL', 'https://apis.quran.foundation/content/api/v4/'),
        'reflect_url' => env('QURAN_REFLECT_API_URL', 'https://apis.quran.foundation/quran-reflect/v1/'),
        'client_id' => env('QURAN_API_KEY'),
        'client_secret' => env('QURAN_API_SECRET'),

        // Relative audio paths returned by QF (e.g. "Alafasy/mp3/001001.mp3",
        // "wbw/001_001_001.mp3") are served from this origin.
        'audio_base' => 'https://verses.quran.foundation/',
        'fonts_base' => 'https://verses.quran.foundation/fonts/quran/hafs/',

        // QF does not publish limits; stay polite. Importers are resumable.
        'rate_limit_per_second' => (int) env('QF_RATE_LIMIT_PER_SECOND', 5),
        'timeout' => (int) env('QF_TIMEOUT', 30),
        'max_retries' => 3,
        'token_early_refresh_seconds' => 30,
    ],

    'mp3quran' => [
        'api_url' => env('MP3QURAN_API_URL', 'https://www.mp3quran.net/api/v3/'),
        'rate_limit_per_second' => 2,
        'timeout' => 30,
    ],

    'mushaf' => [
        // QCF V2 (Madani, 604 pages, 15 lines).
        'default_id' => 1,
    ],

    'audio' => [
        'max_file_mb' => (int) env('QURAN_AUDIO_MAX_FILE_MB', 80),
        'disk' => 'public',
        'path' => 'quran/audio',
    ],

    // Reciter portraits. Quran Foundation ships none, so they come from quran.com's
    // public reciter list; `quran:reciter:images --download` keeps a local copy.
    'profiles' => [
        'reciters_url' => env('QURAN_RECITER_PROFILES_URL', 'https://api.qurancdn.com/api/qdc/audio/reciters'),
        'cdn_url' => env('QURAN_RECITER_PROFILES_CDN', 'https://static.qurancdn.com'),
        'path' => 'quran/reciters',
    ],

    'assets' => [
        'images_path' => 'quran/verse-images',
        'fonts_public_path' => 'fonts/quran',
    ],

    'defaults' => [
        // QF translation resource ids pre-selected per UI locale on the reader.
        // 20 = Saheeh International (131, The Clear Quran, is not in the public
        // catalogue — verified against production 2026-09-17).
        'translations' => [
            'ar' => [],
            'en' => [20],
            'fr' => [],
            'tr' => [],
            'ur' => [],
        ],
        'wbw_language' => 'en',
        // QF translation resource that is a full-ayah Latin transliteration.
        'transliteration_qf_id' => 57,
        'script' => 'text_uthmani',
    ],

    'sync' => [
        'per_page' => 100,
        'schedule' => '03:15',
    ],

    // Ceiling for any API `per_page` parameter (matches QF).
    'api' => [
        'max_per_page' => 50,
    ],

];
