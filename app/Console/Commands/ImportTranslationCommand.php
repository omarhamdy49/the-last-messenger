<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Quran\Models\TranslationResource;
use App\Domain\Quran\Models\Verse;
use App\Domain\Quran\Models\VerseTranslation;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Imports one translation edition from the Quran.com API (v4) into a translation
 * resource, enabling quran.com-style multi-edition translations per language.
 * Idempotent (updateOrCreate keyed by verse + resource); safe to re-run.
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
    protected $signature = 'quran:import-translation {edition : catalog slug or Quran.com translation resource id}
        {--default : Mark this edition as the default for its language}';

    protected $description = 'Import a Quran translation edition from the Quran.com API into a translation resource';

    private const BASE = 'https://api.quran.com/api/v4';

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

            $rows = $resp->json('translations') ?? [];
            $ayah = 0;
            foreach ($rows as $row) {
                $ayah++;
                $verseKey = "{$chapter}:{$ayah}";
                $verseId = $verseMap[$verseKey] ?? null;
                if ($verseId === null) {
                    continue;
                }

                $text = $this->cleanHtml((string) ($row['text'] ?? ''));
                if ($text === '') {
                    continue;
                }

                VerseTranslation::updateOrCreate(
                    ['verse_id' => $verseId, 'resource_id' => $resource->id],
                    ['lang' => $lang, 'text' => $text, 'text_notashkeel' => null],
                );
                $imported++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        // Reader pages cache translation payloads; clear so the new edition shows up.
        Cache::flush();

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
                    $lang = $this->mapLang($t['language_name'] ?? 'en');
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

    private function mapLang(string $languageName): string
    {
        return [
            'arabic' => 'ar', 'english' => 'en', 'french' => 'fr',
            'turkish' => 'tr', 'urdu' => 'ur',
        ][mb_strtolower($languageName)] ?? mb_substr($languageName, 0, 2);
    }

    /**
     * Strip footnote/markup the API embeds (e.g. <sup foot_note>) into clean text.
     */
    private function cleanHtml(string $html): string
    {
        $html = preg_replace('/<\s*(br|\/p|\/h[1-6]|\/div|\/li)\s*\/?>/i', "\n", $html) ?? $html;
        $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/[ \t]+/', ' ', $text) ?? $text;
        $text = preg_replace("/\n{3,}/", "\n\n", $text) ?? $text;

        return trim($text);
    }
}
