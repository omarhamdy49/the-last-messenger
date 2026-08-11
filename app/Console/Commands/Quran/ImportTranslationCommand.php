<?php

declare(strict_types=1);

namespace App\Console\Commands\Quran;

use App\Domain\Quran\Importers\QuranComPayload;
use App\Domain\Quran\Models\TranslationResource;
use App\Domain\Quran\Models\Verse;
use App\Domain\Quran\Models\VerseTranslation;
use App\Domain\Quran\Services\QuranCacheKeys;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Imports one translation edition from the Quran.com API (v4) into a translation
 * resource, enabling quran.com-style multi-edition translations per language.
 * Idempotent (upserted on the (verse_id, resource_id) unique index); safe to re-run.
 *
 * Accepts a catalog slug (config/quran-editions.php) or a raw Quran.com numeric
 * resource id. Run after Quran verses are imported.
 *
 * Examples:
 *   php artisan quran:import-translation en-pickthall
 *   php artisan quran:import-translation 19
 */
class ImportTranslationCommand extends Command
{
    protected $hidden = true;

    protected $signature = 'quran:import-translation {edition : catalog slug or Quran.com translation resource id}
        {--default : Mark this edition as the default for its language}';

    protected $description = 'Import a Quran translation edition from the Quran.com API into a translation resource';

    private const BASE = 'https://api.quran.com/api/v4';

    /**
     * Rows per upsert statement. Keeps the longest chapter (286 ayahs) well
     * inside max_allowed_packet even for verbose editions.
     */
    private const UPSERT_CHUNK = 200;

    public function handle(): int
    {
        $entry = $this->resolveEdition((string) $this->argument('edition'));
        if ($entry === null) {
            $this->error('Unknown edition — pass a catalog slug from config/quran-editions.php or a Quran.com numeric id.');

            return self::FAILURE;
        }

        $quranComId = (int) $entry['quran_com_id'];
        $slug = (string) $entry['slug'];
        $lang = (string) $entry['lang'];

        // Map chapter number → [verseNumber => verseId] so per-chapter, in-order
        // translation rows map deterministically to ayahs (the endpoint returns
        // text in ayah order without a verse_key).
        $verseMap = Verse::query()->pluck('id', 'verse_key');
        if ($verseMap->isEmpty()) {
            $this->error('No verses found — import Quran verses first.');

            return self::FAILURE;
        }

        $resource = TranslationResource::updateOrCreate(
            ['slug' => $slug],
            [
                'name' => $entry['name'],
                'author' => $entry['author'] ?? null,
                'language' => $lang,
                'direction' => in_array($lang, ['ar', 'ur'], true) ? 'rtl' : 'ltr',
                'source' => 'quran.com',
                'is_active' => true,
            ],
        );

        if ($this->option('default') || ! empty($entry['default'])) {
            // Exactly one default per language.
            TranslationResource::where('language', $lang)->update(['is_default' => false]);
            $resource->update(['is_default' => true]);
        }

        $this->info("Resource '{$slug}' (#{$resource->id}, lang={$lang}) — fetching translation #{$quranComId} from Quran.com…");

        $imported = 0;
        $bar = $this->output->createProgressBar(114);

        for ($chapter = 1; $chapter <= 114; $chapter++) {
            try {
                $resp = Http::timeout(30)->retry(3, 600)->get(
                    self::BASE."/quran/translations/{$quranComId}",
                    ['chapter_number' => $chapter],
                );
            } catch (Throwable $e) {
                $this->warn(" chapter {$chapter}: {$e->getMessage()}");
                $bar->advance();

                continue;
            }

            if (! $resp->ok()) {
                $bar->advance();

                continue;
            }

            $rows = $this->chapterRows($resp->json('translations') ?? [], $chapter, $verseMap, $resource->id, $lang);
            $imported += $this->storeChapter($rows);

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        // Reader pages cache translation payloads; clear so the new edition shows up.
        QuranCacheKeys::flushContent();

        $this->info("Imported {$imported} translation entries into '{$slug}' (#{$resource->id}).");

        return self::SUCCESS;
    }

    /**
     * Resolve a catalog slug or numeric id to a normalized edition entry.
     *
     * @return array{slug:string,lang:string,quran_com_id:int,name:array<string,string>,author:?string,default?:bool}|null
     */
    private function resolveEdition(string $edition): ?array
    {
        $catalog = config('quran-editions.translations', []);

        if (is_numeric($edition)) {
            foreach ($catalog as $entry) {
                if ((int) ($entry['quran_com_id'] ?? 0) === (int) $edition) {
                    return $entry;
                }
            }

            // Not in catalog — fetch metadata so a raw id still works.
            return $this->fetchTranslationMeta((int) $edition);
        }

        foreach ($catalog as $entry) {
            if (($entry['slug'] ?? null) === $edition) {
                return $entry;
            }
        }

        return null;
    }

    /**
     * @return array{slug:string,lang:string,quran_com_id:int,name:array<string,string>,author:?string}|null
     */
    private function fetchTranslationMeta(int $id): ?array
    {
        try {
            $resp = Http::timeout(20)->retry(2, 500)->get(self::BASE.'/resources/translations');
            if (! $resp->ok()) {
                return null;
            }
            foreach (($resp->json('translations') ?? []) as $t) {
                if ((int) ($t['id'] ?? 0) === $id) {
                    $lang = QuranComPayload::mapLang($t['language_name'] ?? 'en');
                    $name = (string) ($t['name'] ?? "translation-{$id}");

                    return [
                        'slug' => "{$lang}-".str($name)->slug()->toString(),
                        'lang' => $lang,
                        'quran_com_id' => $id,
                        'name' => ['ar' => $name, 'en' => $name],
                        'author' => $t['author_name'] ?? null,
                    ];
                }
            }
        } catch (Throwable) {
            // fall through to null
        }

        return null;
    }

    /**
     * Map one chapter's API rows onto quran_verse_translations rows.
     *
     * The endpoint returns text in ayah order without a verse_key, so position
     * in the response is the ayah number.
     *
     * @param  array<int, array<string, mixed>>  $apiRows
     * @param  Collection<string, int>  $verseMap  verse_key => verse id
     * @return list<array<string, mixed>>
     */
    private function chapterRows(array $apiRows, int $chapter, Collection $verseMap, int $resourceId, string $lang): array
    {
        $rows = [];
        $ayah = 0;

        foreach ($apiRows as $row) {
            $ayah++;
            $verseId = $verseMap["{$chapter}:{$ayah}"] ?? null;
            if ($verseId === null) {
                continue;
            }

            $text = QuranComPayload::cleanHtml((string) ($row['text'] ?? ''));
            if ($text === '') {
                continue;
            }

            // Keyed by verse id so a duplicated ayah in the response collapses.
            $rows[$verseId] = [
                'verse_id' => $verseId,
                'resource_id' => $resourceId,
                'lang' => $lang,
                'text' => $text,
                'text_notashkeel' => null,
            ];
        }

        return array_values($rows);
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
                VerseTranslation::query()->upsert(
                    $chunk,
                    ['verse_id', 'resource_id'],
                    ['lang', 'text', 'text_notashkeel'],
                );
            }
        });

        return count($rows);
    }
}
