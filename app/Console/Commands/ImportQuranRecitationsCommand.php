<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Quran\Models\Recitation;
use App\Domain\Quran\Models\Reciter;
use App\Domain\Quran\Services\QuranCacheKeys;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Imports per-surah (whole-chapter) recitation audio from the Quran.com API into
 * quran_recitations, which backs the surah-level player on the public reader.
 * Per-verse audio is a separate path — it is built client-side from the reciter's
 * `everyayah_folder` and needs no rows here.
 *
 * Idempotent (updateOrCreate keyed by reciter + surah); safe to re-run.
 *
 * Examples:
 *   php artisan quran:import-recitations
 *   php artisan quran:import-recitations --only=mishary-rashid-alafasy
 */
class ImportQuranRecitationsCommand extends Command
{
    protected $signature = 'quran:import-recitations
        {--only= : Comma-separated reciter slugs to import}';

    protected $description = 'Import per-surah recitation audio URLs from the Quran.com API into quran_recitations';

    private const BASE = 'https://api.quran.com/api/v4';

    /**
     * Reciter id (an everyayah catalogue id — see quran:import-reciters) =>
     * Quran.com chapter-recitation id.
     *
     * Each pairing was verified against the audio URL the endpoint returns, not
     * guessed from the id: Quran.com's chapter-recitation ids are a *different*
     * sequence from its verse-recitation ids and they diverge past 10.
     *
     * Quran.com publishes whole-chapter audio for only a fraction of the
     * catalogue. Every other reciter plays a surah by chaining their per-verse
     * files, so an absent entry here is not a gap in the reader.
     *
     * @var array<int, int>
     */
    private const RECITER_MAP = [
        3 => 1,    // Abdul Basit — Mujawwad
        2 => 2,    // Abdul Basit — Murattal
        8 => 3,    // Abdurrahmaan As-Sudais
        11 => 4,   // Abu Bakr Ash-Shaatree
        18 => 5,   // Hani Rifai
        20 => 6,   // Husary
        15 => 7,   // Alafasy
        33 => 8,   // Minshawy — Mujawwad
        34 => 9,   // Minshawy — Murattal
        44 => 10,  // Saood ash-Shuraym
        52 => 11,  // Muhsin Al Qasim
        65 => 12,  // Husary — Muallim
        16 => 13,  // Ghamadi
        77 => 14,  // Fares Abbad
        70 => 17,  // Sahl Yassin
        51 => 18,  // Salaah Bukhatir
        71 => 19,  // Ahmed Ibn Ali Al Ajamy
        37 => 22,  // Muhammad Ayyoub
    ];

    public function handle(): int
    {
        $only = array_filter(array_map('trim', explode(',', (string) $this->option('only'))));

        $slugs = Reciter::query()->pluck('slug', 'id');
        if ($slugs->isEmpty()) {
            $this->error('No reciters found — run quran:import-reciters first.');

            return self::FAILURE;
        }

        $imported = 0;
        $skipped = [];

        foreach (self::RECITER_MAP as $reciterId => $quranComId) {
            $slug = $slugs[$reciterId] ?? null;
            if ($slug === null) {
                $skipped[] = "reciter #{$reciterId} (not in quran_reciters)";

                continue;
            }

            if ($only && ! in_array($slug, $only, true)) {
                continue;
            }

            $this->line("→ {$slug} (quran.com #{$quranComId})");

            try {
                $resp = Http::timeout(60)->retry(3, 800)->get(self::BASE."/chapter_recitations/{$quranComId}");
            } catch (Throwable $e) {
                $skipped[] = "{$slug} ({$e->getMessage()})";

                continue;
            }

            if (! $resp->ok()) {
                $skipped[] = "{$slug} (HTTP {$resp->status()})";

                continue;
            }

            $count = 0;
            foreach ($resp->json('audio_files') ?? [] as $file) {
                $surahId = (int) ($file['chapter_id'] ?? 0);
                $url = (string) ($file['audio_url'] ?? '');
                if ($surahId < 1 || $surahId > 114 || $url === '') {
                    continue;
                }

                Recitation::updateOrCreate(
                    ['reciter_id' => $reciterId, 'surah_id' => $surahId],
                    [
                        'audio_url' => $url,
                        'format' => (string) ($file['format'] ?? 'mp3'),
                        // The API reports bytes; the column stores KB.
                        'file_size_kb' => isset($file['file_size'])
                            ? (int) round(((float) $file['file_size']) / 1024)
                            : null,
                    ],
                );
                $count++;
                $imported++;
            }

            $this->line("   {$count} surahs");
        }

        // The reader caches per-surah audio URL maps; clear so the player picks these up.
        QuranCacheKeys::flushReciters();

        $this->newLine();
        // Report stored rows, not API entries — a few endpoints list a chapter
        // more than once, and updateOrCreate collapses those.
        $stored = Recitation::query()->count();
        $this->info("Imported {$imported} audio files into {$stored} recitation rows.");

        foreach ($skipped as $note) {
            $this->warn("Skipped: {$note}");
        }

        return self::SUCCESS;
    }
}
