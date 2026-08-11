<?php

declare(strict_types=1);

namespace App\Console\Commands\Quran;

use App\Domain\Quran\Importers\QuranComPayload;
use App\Domain\Quran\Models\TafsirResource;
use App\Domain\Quran\Models\Verse;
use App\Domain\Quran\Models\VerseTafsir;
use App\Domain\Quran\Services\QuranCacheKeys;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Imports a tafsir from the Quran.com API (v4) into a tafsir resource, enabling
 * quran.com-style multi-source tafsir. Idempotent (upserted on the
 * (verse_id, resource_id) unique index); safe to re-run. Run after Quran
 * verses are imported.
 *
 * Examples:
 *   php artisan quran:import-tafsir 169   # Ibn Kathir (Abridged), English
 *   php artisan quran:import-tafsir 91    # As-Sa'di, Arabic
 */
class ImportTafsirCommand extends Command
{
    protected $hidden = true;

    protected $signature = 'quran:import-tafsir {tafsirId : Quran.com tafsir resource id}
        {--slug=} {--lang=} {--name-ar=} {--name-en=} {--author=} {--source=quran.com}';

    protected $description = 'Import a tafsir from the Quran.com API into a tafsir resource';

    private const BASE = 'https://api.quran.com/api/v4';

    /**
     * Rows per upsert statement. Tafsir entries can run to several KB each, so
     * this stays conservative to keep statements inside max_allowed_packet.
     */
    private const UPSERT_CHUNK = 100;

    public function handle(): int
    {
        $tafsirId = (int) $this->argument('tafsirId');
        $meta = $this->fetchTafsirMeta($tafsirId);

        $slug = (string) ($this->option('slug') ?: ($meta['slug'] ?? "tafsir-{$tafsirId}"));
        $lang = (string) ($this->option('lang') ?: QuranComPayload::mapLang($meta['language_name'] ?? 'ar'));
        $nameEn = (string) ($this->option('name-en') ?: ($meta['name'] ?? $slug));
        $nameAr = (string) ($this->option('name-ar') ?: $nameEn);
        $author = $this->option('author') ?: ($meta['author_name'] ?? null);

        $verseMap = Verse::query()->pluck('id', 'verse_key');
        if ($verseMap->isEmpty()) {
            $this->error('No verses found — import Quran verses first.');

            return self::FAILURE;
        }

        $resource = TafsirResource::updateOrCreate(
            ['slug' => $slug],
            [
                'name' => ['ar' => $nameAr, 'en' => $nameEn],
                'author' => $author ?: null,
                'language' => $lang,
                'source' => (string) $this->option('source'),
                'is_active' => true,
            ],
        );

        $this->info("Resource '{$slug}' (#{$resource->id}, lang={$lang}) — fetching from Quran.com…");

        $imported = 0;
        $bar = $this->output->createProgressBar(114);

        for ($chapter = 1; $chapter <= 114; $chapter++) {
            $page = 1;
            $rows = [];
            do {
                try {
                    $resp = Http::timeout(30)->retry(3, 600)->get(
                        self::BASE."/tafsirs/{$tafsirId}/by_chapter/{$chapter}",
                        ['per_page' => 50, 'page' => $page],
                    );
                } catch (Throwable $e) {
                    $this->warn(" chapter {$chapter} p{$page}: {$e->getMessage()}");
                    break;
                }

                if (! $resp->ok()) {
                    break;
                }

                $data = $resp->json();
                // Accumulate every page of the chapter, then write it in one go.
                $rows += $this->chapterRows($data['tafsirs'] ?? [], $verseMap, $resource->id, $lang);

                $totalPages = (int) ($data['pagination']['total_pages'] ?? 1);
                $page++;
            } while ($page <= $totalPages);

            $imported += $this->storeChapter(array_values($rows));

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        // Reader pages cache tafsir payloads; clear so the new source shows up.
        QuranCacheKeys::flushContent();

        $this->info("Imported {$imported} tafsir entries into '{$slug}' (#{$resource->id}).");

        return self::SUCCESS;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function fetchTafsirMeta(int $id): ?array
    {
        try {
            $resp = Http::timeout(20)->retry(2, 500)->get(self::BASE.'/resources/tafsirs');
            if (! $resp->ok()) {
                return null;
            }
            foreach (($resp->json('tafsirs') ?? []) as $t) {
                if ((int) ($t['id'] ?? 0) === $id) {
                    return $t;
                }
            }
        } catch (Throwable) {
            // fall through to null; caller falls back to options/defaults
        }

        return null;
    }

    /**
     * Map one API page onto quran_verse_tafsirs rows, keyed by verse id so
     * repeated verse_keys across pages collapse to one row.
     *
     * @param  array<int, array<string, mixed>>  $apiRows
     * @param  Collection<string, int>  $verseMap  verse_key => verse id
     * @return array<int, array<string, mixed>>
     */
    private function chapterRows(array $apiRows, Collection $verseMap, int $resourceId, string $lang): array
    {
        $rows = [];

        foreach ($apiRows as $row) {
            $verseKey = $row['verse_key'] ?? null;
            $verseId = $verseKey ? ($verseMap[$verseKey] ?? null) : null;
            if ($verseId === null) {
                continue;
            }

            $text = QuranComPayload::cleanHtml((string) ($row['text'] ?? ''));
            if ($text === '') {
                continue;
            }

            $rows[$verseId] = [
                'verse_id' => $verseId,
                'resource_id' => $resourceId,
                'verse_key' => $verseKey,
                'lang' => $lang,
                'text' => $text,
                'active' => true,
            ];
        }

        return $rows;
    }

    /**
     * Upsert one chapter's rows in a transaction.
     *
     * Safe as a plain upsert: resource_id is never NULL here, so the
     * (verse_id, resource_id) unique index actually constrains these rows.
     *
     * @param  list<array<string, mixed>>  $rows
     * @return int rows written
     */
    private function storeChapter(array $rows): int
    {
        if ($rows === []) {
            return 0;
        }

        DB::transaction(function () use ($rows): void {
            foreach (array_chunk($rows, self::UPSERT_CHUNK) as $chunk) {
                VerseTafsir::query()->upsert(
                    $chunk,
                    ['verse_id', 'resource_id'],
                    ['verse_key', 'lang', 'text', 'active'],
                );
            }
        });

        return count($rows);
    }
}
