<?php

declare(strict_types=1);

namespace App\Domain\Quran\Infrastructure\Audio;

use App\Domain\Quran\Models\RecordingSource;
use Illuminate\Support\Facades\Http;
use RuntimeException;

final class Mp3QuranProvider extends DirectAudioProvider
{
    public function timings(RecordingSource $source, int $surah): array
    {
        if ($source->external_timing_id === null) {
            return [];
        }
        $response = Http::timeout(10)->retry(2, 500, throw: false)->get(config('quran-audio.timing_endpoint'), [
            'surah' => $surah, 'read' => $source->external_timing_id,
        ]);
        if (! $response->successful() || ! is_array($response->json())) {
            throw new RuntimeException('Audio timing provider returned an invalid response.');
        }

        return $response->json();
    }
}
