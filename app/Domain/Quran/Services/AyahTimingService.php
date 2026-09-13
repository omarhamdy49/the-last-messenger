<?php

declare(strict_types=1);

namespace App\Domain\Quran\Services;

use App\Domain\Quran\Models\Recitation;
use App\Domain\Quran\Models\RecordingSource;
use App\Domain\Quran\Models\Surah;
use App\Domain\Quran\Repositories\RecitationRepository;
use App\Domain\Quran\Services\Audio\RecordingSourceService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

/**
 * Per-ayah start/end times for a whole-surah recitation, from mp3quran.net.
 *
 * The player has one MP3 per surah and needs to know which ayah is being
 * recited at any moment. mp3quran publishes that for the recordings Phase 1
 * adopted, keyed by the recording's `read` id — which is why a reciter without
 * a source timing reference has no timings: the times only describe
 * mp3quran's own audio.
 *
 * Everything degrades to an empty list. Highlighting is progressive
 * enhancement; a dead upstream must not cost the reader their audio.
 */
final class AyahTimingService
{
    /** Timings for a published recording never change. */
    private const HIT_TTL = 30 * 24 * 3600;

    /** A miss is almost always a transient upstream failure — retry soon. */
    private const MISS_TTL = 300;

    /**
     * @return array{surah: int, reciter_id: int, ayahs: list<array{int, int, int}>}
     */
    public function forSurah(int $surahNumber, int $reciterId, ?int $sourceId = null, ?int $revision = null): array
    {
        return [
            'surah' => $surahNumber,
            'reciter_id' => $reciterId,
            'ayahs' => $this->ayahs($surahNumber, $reciterId, $sourceId, $revision),
        ];
    }

    /**
     * @return list<array{int, int, int}>
     */
    private function ayahs(int $surahNumber, int $reciterId, ?int $sourceId, ?int $revision): array
    {
        if ($sourceId === null) {
            $sourceId = app(RecitationRepository::class)->getSurahAudioSources($surahNumber)[$reciterId]['source_id'] ?? null;
        }
        $source = $sourceId ? RecordingSource::where('recording_id', $reciterId)->where('is_active', true)->find($sourceId) : null;
        if (! $source || $source->external_timing_id === null || ($revision !== null && $source->revision !== $revision)) {
            return [];
        }
        if (! Recitation::where('source_id', $source->id)->where('surah_id', $surahNumber)->exists()) {
            return [];
        }
        $key = QuranCacheKeys::reciterScoped("timings:source:{$source->id}:{$source->revision}:{$surahNumber}");
        $cached = Cache::get($key);

        if (is_array($cached)) {
            return $cached;
        }

        $ayahs = $this->fetch($surahNumber, $source);

        Cache::put($key, $ayahs, $ayahs === [] ? self::MISS_TTL : self::HIT_TTL);

        return $ayahs;
    }

    /**
     * @return list<array{int, int, int}> [ayah, startMs, endMs]; empty on any failure
     */
    private function fetch(int $surahNumber, RecordingSource $source): array
    {
        try {
            $readId = (int) $source->external_timing_id;
            $rows = app(RecordingSourceService::class)->adapter($source)->timings($source, $surahNumber);

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

            return $this->reconcileIndices($ayahs, $surahNumber, $readId);
        } catch (Throwable $e) {
            report($e);

            return [];
        }
    }

    /**
     * Make the upstream indices real ayah numbers, or refuse to serve them.
     *
     * mp3quran uses more than one convention. Most reads open with an ayah 0
     * bismillah row and count real ayat after it; some (Alafasy) have no
     * bismillah row at all. Both are fine. But Husary's al-Fatiha returns
     * 0..6 for a 7-ayah surah — his "ayah 0" is really ayah 1 and every index
     * below it is one short, which would highlight the whole surah a verse
     * behind. Anything we cannot explain is dropped: no highlighting beats
     * highlighting the wrong verse of the Quran.
     *
     * @param  list<array{int, int, int}>  $ayahs  sorted by ayah
     * @return list<array{int, int, int}>
     */
    private function reconcileIndices(array $ayahs, int $surahNumber, int $readId): array
    {
        // The surah number is the primary key — quran_surahs has no `number` column.
        $expected = (int) Surah::query()->whereKey($surahNumber)->value('verses_count');
        $indices = array_column($ayahs, 0);
        $where = "surah={$surahNumber} read={$readId}";

        if ($expected < 1) {
            throw new RuntimeException("mp3quran ayat_timing {$where}: no known ayah count to check against.");
        }

        if (count(array_unique($indices)) !== count($indices)) {
            throw new RuntimeException("mp3quran ayat_timing {$where}: duplicate ayah indices.");
        }

        foreach ($ayahs as $i => [, $start, $end]) {
            if ($end < $start || ($i > 0 && $start < $ayahs[$i - 1][1])) {
                throw new RuntimeException("mp3quran ayat_timing {$where}: times are not monotonic.");
            }
        }

        $max = max($indices);

        if ($max === $expected) {
            return $ayahs;
        }

        if ($max === $expected - 1 && $indices[0] === 0) {
            // Shifted: the first row is genuinely ayah 1, not the bismillah.
            Log::info("mp3quran ayat_timing {$where}: indices shifted by one, corrected.");

            return array_map(static fn (array $t): array => [$t[0] + 1, $t[1], $t[2]], $ayahs);
        }

        throw new RuntimeException("mp3quran ayat_timing {$where}: max ayah {$max}, expected {$expected}.");
    }
}
