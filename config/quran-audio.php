<?php

use App\Domain\Quran\Infrastructure\Audio\DirectAudioProvider;
use App\Domain\Quran\Infrastructure\Audio\EveryAyahProvider;
use App\Domain\Quran\Infrastructure\Audio\Mp3QuranProvider;

return [
    'providers' => [
        'direct' => DirectAudioProvider::class,
        'local' => DirectAudioProvider::class,
        'everyayah' => EveryAyahProvider::class,
        'mp3quran' => Mp3QuranProvider::class,
    ],
    'verse_base_url' => 'https://everyayah.com/data',
    'timing_endpoint' => 'https://www.mp3quran.net/api/v3/ayat_timing',
    'default_recording_slug' => 'husary',
];
