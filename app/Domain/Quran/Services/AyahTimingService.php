<?php

declare(strict_types=1);

namespace App\Domain\Quran\Services;

use App\Domain\Quran\Models\Reciter;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

/**
 * Per-ayah start/end times for a whole-surah recitation, from mp3quran.net.
 *
 * The player has one MP3 per surah and needs to know which ayah is being
 * recited at any moment. mp3quran publishes that for the recordings Phase 1
 * adopted, keyed by the recording's `read` id — which is why a reciter without
 * an `mp3quran_read_id` has no timings and never will: the times only describe
 * mp3quran's own audio.
 *
 * Everything degrades to an empty list. Highlighting is progressive
 * enhancement; a dead upstream must not cost the reader their audio.
 */
final class AyahTimingService
{
    private const ENDPOINT = 'https://www.mp3quran.net/api/v3/ayat_timing';

    /** Timings for a published recording never change. */
    private const HIT_TTL = 30 * 24 * 3600;

    /** A miss is almost always a transient upstream failure — retry soon. */
    private const MISS_TTL = 300;

    /**
     * @return array{surah: int, reciter_id: int, ayahs: list<array{int, int, int}>}
     */
    public function forSurah(int $surahNumber, int $reciterId): array
    {
        return [
            'surah' => $surahNumber,
            'reciter_id' => $reciterId,
            'ayahs' => $this->ayahs($surahNumber, $reciterId),
        ];
    }

    /**
     * @return list<array{int, int, int}>
     */
    private function ayahs(int $surahNumber, int $reciterId): array
    {
        $readId = Reciter::query()->whereKey($reciterId)->value('mp3quran_read_id');

        if ($readId === null) {
            return [];
        }

        $key = QuranCacheKeys::surahAyahTimings($surahNumber, $reciterId);
        $cached = Cache::get($key);

        if (is_array($cached)) {
            return $cached;
        }

        $ayahs = $this->fetch($surahNumber, (int) $readId);

        Cache::put($key, $ayahs, $ayahs === [] ? self::MISS_TTL : self::HIT_TTL);

        return $ayahs;
    }

    /**
     * @return list<array{int, int, int}> [ayah, startMs, endMs]; empty on any failure
     */
    private function fetch(int $surahNumber, int $readId): array
    {
        try {
            $response = Http::timeout(10)->retry(2, 500, throw: false)
                ->get(self::ENDPOINT, ['surah' => $surahNumber, 'read' => $readId]);

            if (! $response->successful()) {
                throw new RuntimeException(
                    "mp3quran ayat_timing surah={$surahNumber} read={$readId}: HTTP {$response->status()}"
                );
            }

            $rows = $response->json();

            if (! is_array($rows)) {
                throw new RuntimeException("mp3quran ayat_timing surah={$surahNumber} read={$readId}: body is not JSON.");
            }

            $ayahs = [];

            foreach ($rows as $row) {
                // Drop polygon/x/y/page: we render our own mushaf layout, and
                // carrying them would roughly triple the payload.
                if (! is_array($row) || ! isset($row['ayah'], $row['start_time'], $row['end_time'])) {
                    continue;
                }

                $ayahs[] = [(int) $row['ayah'], (int) $row['start_time'], (int) $row['end_time']];
            }

            if ($ayahs === []) {
                throw new RuntimeException(
                    "mp3quran ayat_timing surah={$surahNumber} read={$readId}: no usable timings in the response."
                );
            }

            // Ayah 0 is the bismillah and is kept — the player uses it to
            // highlight nothing while it plays.
            usort($ayahs, static fn (array $a, array $b) => $a[0] <=> $b[0]);

            return $ayahs;
        } catch (Throwable $e) {
            report($e);

            return [];
        }
    }
}
