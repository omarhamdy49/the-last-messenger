<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Quran\Models\Verse;
use App\Domain\Quran\Models\VerseTranslation;
use App\Domain\Quran\Services\QuranCacheKeys;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Syncs the Arabic verse text (lang=ar) from the Quran.com API's Uthmani script
 * so it exactly matches quran.com — fixing first verses that wrongly carried the
 * Basmala (e.g. 2:1 was "بِسْمِ... الٓمٓ"; correct is "الٓمٓ") and script variants.
 * Idempotent; safe to re-run.
 */
class SyncQuranArabicCommand extends Command
{
    protected $signature = 'quran:sync-arabic {--chapter= : sync a single chapter only}';

    protected $description = 'Sync Arabic verse text from the Quran.com Uthmani API (matches quran.com exactly)';

    private const BASE = 'https://api.quran.com/api/v4';

    public function handle(): int
    {
        $verseMap = Verse::query()->pluck('id', 'verse_key');
        if ($verseMap->isEmpty()) {
            $this->error('No verses found — import Quran verses first.');

            return self::FAILURE;
        }

        $only = $this->option('chapter');
        $chapters = $only ? [(int) $only] : range(1, 114);

        $updated = 0;
        $bar = $this->output->createProgressBar(count($chapters));

        foreach ($chapters as $chapter) {
            try {
                $resp = Http::timeout(30)->retry(3, 600)
                    ->get(self::BASE.'/quran/verses/uthmani', ['chapter_number' => $chapter]);
            } catch (Throwable $e) {
                $this->warn(" chapter {$chapter}: {$e->getMessage()}");
                $bar->advance();

                continue;
            }

            if (! $resp->ok()) {
                $bar->advance();

                continue;
            }

            foreach (($resp->json('verses') ?? []) as $row) {
                $verseKey = $row['verse_key'] ?? null;
                $text = trim((string) ($row['text_uthmani'] ?? ''));
                $verseId = $verseKey ? ($verseMap[$verseKey] ?? null) : null;
                if ($verseId === null || $text === '') {
                    continue;
                }

                VerseTranslation::query()
                    ->updateOrCreate(
                        ['verse_id' => $verseId, 'lang' => 'ar'],
                        ['text' => $text, 'text_notashkeel' => $this->stripDiacritics($text)],
                    );
                $updated++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        QuranCacheKeys::flushContent();
        $this->info("Synced Arabic text for {$updated} verses from quran.com.");

        return self::SUCCESS;
    }

    /**
     * Remove Arabic diacritics/tatweel for the search-friendly column.
     */
    private function stripDiacritics(string $text): string
    {
        $clean = preg_replace('/[\x{0610}-\x{061A}\x{064B}-\x{065F}\x{0670}\x{06D6}-\x{06ED}\x{0640}]/u', '', $text);

        return trim((string) ($clean ?? $text));
    }
}
