<?php

declare(strict_types=1);

namespace App\Domain\Quran\Services;

use App\Domain\Quran\Models\Verse;
use App\Domain\Quran\Models\Word;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Imports mushaf word-layout (line + page + order per word) from the quran.com
 * API into quran_words.
 *
 * We take only the real Unicode text (text_uthmani) and the layout fields — NOT
 * the code_v2 glyph fonts — so the public reader can reproduce printed-mushaf
 * line breaks and page order using the site's own Uthmani font.
 *
 * Idempotent: rows are upserted on the unique `location` key, so re-running is
 * safe and repairs a partial import.
 */
class MushafLayoutImportService
{
    public const TOTAL_PAGES = 604;

    private const BASE = 'https://api.quran.com/api/v4';

    private const API_PER_PAGE = 50;

    private const UPSERT_CHUNK_SIZE = 500;

    /**
     * Import the given mushaf pages.
     *
     * @param  list<int>  $pages
     * @param  (callable(int $page, int $wordsWritten): void)|null  $onPage  per-page progress hook
     * @return array{pages: int, words: int}
     */
    public function importPages(array $pages, ?callable $onPage = null): array
    {
        $verseIdByKey = $this->verseIdByKey();

        $totalWords = 0;
        foreach ($pages as $page) {
            $rows = $this->fetchPageRows($page, $verseIdByKey);
            $written = $this->upsertChunked($rows);
            $totalWords += $written;

            if ($onPage !== null) {
                $onPage($page, $written);
            }
        }

        // Quran data is cached aggressively; drop it so the reader reflects the import.
        if ($totalWords > 0) {
            QuranCacheKeys::flushContent();
        }

        return ['pages' => count($pages), 'words' => $totalWords];
    }

    /**
     * Fetch every word on one mushaf page, mapped to quran_words rows.
     * Follows API pagination so dense pages (many short verses) are complete.
     *
     * @param  array<string, int>  $verseIdByKey
     * @return list<array<string, mixed>>
     */
    private function fetchPageRows(int $page, array $verseIdByKey): array
    {
        $rows = [];
        $apiPage = 1;

        do {
            $response = Http::timeout(30)->retry(3, 600)->get(self::BASE."/verses/by_page/{$page}", [
                'words' => 'true',
                'word_fields' => 'text_uthmani,line_number,page_number,location,char_type_name',
                'per_page' => self::API_PER_PAGE,
                'page' => $apiPage,
            ]);

            if (! $response->ok()) {
                throw new RuntimeException("quran.com API returned {$response->status()} for mushaf page {$page}.");
            }

            foreach (($response->json('verses') ?? []) as $verse) {
                foreach (($verse['words'] ?? []) as $word) {
                    $row = $this->mapWordRow($word, $verseIdByKey);
                    if ($row !== null) {
                        $rows[] = $row;
                    }
                }
            }

            $next = $response->json('pagination.next_page');
            $apiPage = is_int($next) ? $next : null;
        } while ($apiPage !== null);

        if ($rows === []) {
            throw new RuntimeException("No words returned for mushaf page {$page}.");
        }

        return $rows;
    }

    /**
     * Map one API word to a quran_words row, or null if it can't be placed
     * (malformed location, or a verse not present locally).
     *
     * @param  array<string, mixed>  $word
     * @param  array<string, int>  $verseIdByKey
     * @return array<string, mixed>|null
     */
    private function mapWordRow(array $word, array $verseIdByKey): ?array
    {
        $location = (string) ($word['location'] ?? '');
        $parts = explode(':', $location);
        if (count($parts) !== 3) {
            return null;
        }

        [$surahNumber, $verseNumber, $position] = array_map('intval', $parts);
        $verseId = $verseIdByKey["{$surahNumber}:{$verseNumber}"] ?? null;
        if ($verseId === null) {
            return null;
        }

        return [
            'verse_id' => $verseId,
            'surah_number' => $surahNumber,
            'verse_number' => $verseNumber,
            'position' => $position,
            'location' => $location,
            'text_uthmani' => (string) ($word['text_uthmani'] ?? $word['text'] ?? ''),
            'char_type' => (string) ($word['char_type_name'] ?? 'word'),
            'line_number' => (int) ($word['line_number'] ?? 0),
            'page_number' => (int) ($word['page_number'] ?? 0),
        ];
    }

    /**
     * verse_key => verse_id, for mapping API word locations to local verse ids.
     *
     * @return array<string, int>
     */
    private function verseIdByKey(): array
    {
        $map = Verse::query()->pluck('id', 'verse_key')->all();

        if ($map === []) {
            throw new RuntimeException('quran_verses is empty — import Quran verses before the mushaf layout.');
        }

        return $map;
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    private function upsertChunked(array $rows): int
    {
        return DB::transaction(function () use ($rows): int {
            $written = 0;

            foreach (array_chunk($rows, self::UPSERT_CHUNK_SIZE) as $chunk) {
                Word::upsert(
                    $chunk,
                    ['location'],
                    ['verse_id', 'surah_number', 'verse_number', 'position', 'text_uthmani', 'char_type', 'line_number', 'page_number'],
                );
                $written += count($chunk);
            }

            return $written;
        });
    }
}
