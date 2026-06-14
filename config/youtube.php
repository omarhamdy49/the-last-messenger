<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | yt-dlp binary
    |--------------------------------------------------------------------------
    | Path to the yt-dlp executable used to fetch YouTube metadata, subtitles
    | and thumbnails. Defaults to the binary on $PATH; set YT_DLP_PATH to an
    | absolute path if it lives elsewhere.
    */
    'yt_dlp_path' => env('YT_DLP_PATH', 'yt-dlp'),

    /*
    | Max seconds a single yt-dlp invocation may run before it is killed.
    | Deep per-video fetches (metadata + subtitles) are the slow path.
    */
    'process_timeout' => (int) env('YT_DLP_TIMEOUT', 600),

    /*
    | Subtitle languages to request, in order of preference. The first
    | language with an available track wins; Arabic is preferred to match
    | the platform's default content language.
    */
    'subtitle_languages' => ['ar', 'en'],

    /*
    | Safety cap on how many videos one playlist import will process. Guards
    | against accidentally importing an enormous playlist in a single run.
    | A truncated import is logged (never silently dropped).
    */
    'max_playlist_episodes' => (int) env('YOUTUBE_MAX_EPISODES', 100),
];
