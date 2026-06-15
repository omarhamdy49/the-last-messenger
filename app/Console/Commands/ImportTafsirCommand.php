<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Quran\Models\TafsirResource;
use App\Domain\Quran\Models\Verse;
use App\Domain\Quran\Models\VerseTafsir;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Imports a tafsir from the Quran.com API (v4) into a tafsir resource, enabling
 * quran.com-style multi-source tafsir. Idempotent (updateOrCreate keyed by
 * verse + resource); safe to re-run. Run after Quran verses are imported.
 *
 * Examples:
 *   php artisan quran:import-tafsir 169   # Ibn Kathir (Abridged), English
 *   php artisan quran:import-tafsir 91    # As-Sa'di, Arabic
 */
class ImportTafsirCommand extends Command
{
    protected $signature = 'quran:import-tafsir {tafsirId : Quran.com tafsir resource id}
        {--slug=} {--lang=} {--name-ar=} {--name-en=} {--author=} {--source=quran.com}';

    protected $description = 'Import a tafsir from the Quran.com API into a tafsir resource';

    private const BASE = 'https://api.quran.com/api/v4';

    public function handle(): int
    {
        $tafsirId = (int) $this->argument('tafsirId');
        $meta = $this->fetchTafsirMeta($tafsirId);

        $slug = (string) ($this->option('slug') ?: ($meta['slug'] ?? "tafsir-{$tafsirId}"));
        $lang = (string) ($this->option('lang') ?: $this->mapLang($meta['language_name'] ?? 'ar'));
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
                foreach (($data['tafsirs'] ?? []) as $row) {
                    $verseKey = $row['verse_key'] ?? null;
                    $verseId = $verseKey ? ($verseMap[$verseKey] ?? null) : null;
                    if ($verseId === null) {
                        continue;
                    }

                    $text = $this->cleanHtml((string) ($row['text'] ?? ''));
                    if ($text === '') {
                        continue;
                    }

                    VerseTafsir::updateOrCreate(
                        ['verse_id' => $verseId, 'resource_id' => $resource->id],
                        ['verse_key' => $verseKey, 'lang' => $lang, 'text' => $text, 'active' => true],
                    );
                    $imported++;
                }

                $totalPages = (int) ($data['pagination']['total_pages'] ?? 1);
                $page++;
            } while ($page <= $totalPages);

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        // Reader pages cache tafsir payloads; clear so the new source shows up.
        Cache::flush();

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

    private function mapLang(string $languageName): string
    {
        return [
            'arabic' => 'ar', 'english' => 'en', 'french' => 'fr',
            'turkish' => 'tr', 'urdu' => 'ur',
        ][mb_strtolower($languageName)] ?? mb_substr($languageName, 0, 2);
    }

    /**
     * Convert the API's HTML tafsir into clean, readable plain text (the reader
     * escapes output, so storing plain text keeps it safe and consistent with the
     * existing Muyassar tafsir).
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
