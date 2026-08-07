<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Quran\Models\Recitation;
use App\Domain\Quran\Models\Reciter;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
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
     * Our reciter slug => Quran.com chapter-recitation id.
     *
     * Quran.com exposes whole-chapter audio only for these reciters; maher-al-muaiqly
     * has no chapter recitation there, so it stays per-verse only (its surah play
     * button falls back to the "audio unavailable" notice).
     *
     * @var array<string, int>
     */
    private const RECITER_MAP = [
        'abdul-basit-abdus-samad-mujawwad' => 1,
        'abdul-basit-abdus-samad-murattal' => 2,
        'abdurrahman-as-sudais' => 3,
        'abu-bakr-al-shatri' => 4,
        'hani-ar-rifai' => 5,
        'mahmoud-khalil-al-husary' => 6,
        'mishary-rashid-alafasy' => 7,
        'mohamed-siddiq-el-minshawi' => 9,
        'saud-al-shuraim' => 10,
        'saad-al-ghamdi' => 13,
    ];

    public function handle(): int
    {
        $only = array_filter(array_map('trim', explode(',', (string) $this->option('only'))));

        $reciters = Reciter::query()->pluck('id', 'slug');
        if ($reciters->isEmpty()) {
            $this->error('No reciters found — seed quran_reciters first.');

            return self::FAILURE;
        }

        $imported = 0;
        $skipped = [];

        foreach (self::RECITER_MAP as $slug => $quranComId) {
            if ($only && ! in_array($slug, $only, true)) {
                continue;
            }

            $reciterId = $reciters[$slug] ?? null;
            if ($reciterId === null) {
                $skipped[] = "{$slug} (not in quran_reciters)";

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
        Cache::flush();

        $this->newLine();
        $this->info("Imported {$imported} recitation rows.");

        foreach ($skipped as $note) {
            $this->warn("Skipped: {$note}");
        }

        return self::SUCCESS;
    }
}
