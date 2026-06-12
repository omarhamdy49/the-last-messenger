<?php

declare(strict_types=1);

namespace App\Domain\Quran\Services;

use App\Domain\Quran\Models\Verse;
use App\Domain\Quran\Models\VerseTafsir;
use App\Domain\Quran\Models\VerseTranslation;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Imports verse-aligned text datasets (Tanzil.net text format) into
 * quran_verse_translations and quran_verse_tafsirs.
 *
 * Tanzil text files contain exactly one verse per line in canonical
 * mushaf order (1:1 .. 114:6 = 6236 lines); trailing comment lines
 * start with '#' and blank lines are ignored.
 */
class QuranTextDatasetImportService
{
    public const EXPECTED_VERSE_COUNT = 6236;

    private const UPSERT_CHUNK_SIZE = 500;

    /**
     * Import a verse-per-line translation file for one language.
     *
     * Idempotent: rows are upserted on the (verse_id, lang) unique key.
     *
     * @return array{lang: string, lines: int, written: int}
     */
    public function importTranslationFile(string $filePath, string $lang): array
    {
        $lines = $this->readVerseLines($filePath);
        $verseIds = $this->getVerseIdsInMushafOrder();

        $rows = [];
        foreach ($lines as $index => $text) {
            $rows[] = [
                'verse_id' => $verseIds[$index],
                'lang' => $lang,
                'text' => $text,
                'text_notashkeel' => null,
            ];
        }

        $written = $this->upsertChunked(
            fn (array $chunk) => VerseTranslation::upsert($chunk, ['verse_id', 'lang'], ['text']),
            $rows,
        );

        return ['lang' => $lang, 'lines' => count($lines), 'written' => $written];
    }

    /**
     * Import a verse-per-line tafsir file for one language.
     *
     * Idempotent: rows are upserted on the (verse_id, lang) unique key.
     *
     * @return array{lang: string, lines: int, written: int}
     */
    public function importTafsirFile(string $filePath, string $lang): array
    {
        $lines = $this->readVerseLines($filePath);
        $verses = $this->getVersesInMushafOrder();

        $rows = [];
        foreach ($lines as $index => $text) {
            $rows[] = [
                'verse_id' => $verses[$index]['id'],
                'verse_key' => $verses[$index]['verse_key'],
                'lang' => $lang,
                'text' => $text,
                'active' => true,
            ];
        }

        $written = $this->upsertChunked(
            fn (array $chunk) => VerseTafsir::upsert($chunk, ['verse_id', 'lang'], ['text', 'verse_key', 'active']),
            $rows,
        );

        return ['lang' => $lang, 'lines' => count($lines), 'written' => $written];
    }

    /**
     * Read a Tanzil-format text file and return exactly 6236 verse lines.
     *
     * @return list<string>
     */
    private function readVerseLines(string $filePath): array
    {
        if (! is_readable($filePath)) {
            throw new InvalidArgumentException("File not found or unreadable: {$filePath}");
        }

        $rawLines = file($filePath, FILE_IGNORE_NEW_LINES);

        if ($rawLines === false) {
            throw new InvalidArgumentException("Unable to read file: {$filePath}");
        }

        $verseLines = [];
        foreach ($rawLines as $line) {
            $trimmed = trim($line);

            if ($trimmed === '' || str_starts_with($trimmed, '#')) {
                continue;
            }

            $verseLines[] = $trimmed;
        }

        if (count($verseLines) !== self::EXPECTED_VERSE_COUNT) {
            throw new InvalidArgumentException(sprintf(
                'Expected %d verse lines in %s, found %d — refusing to import misaligned data.',
                self::EXPECTED_VERSE_COUNT,
                $filePath,
                count($verseLines),
            ));
        }

        return $verseLines;
    }

    /**
     * Verse ids in canonical mushaf order (line N of a Tanzil file = index N-1).
     *
     * @return list<int>
     */
    private function getVerseIdsInMushafOrder(): array
    {
        $ids = Verse::query()
            ->select(['id'])
            ->orderBy('surah_id')
            ->orderBy('verse_number')
            ->pluck('id')
            ->all();

        $this->assertVerseCount(count($ids));

        return $ids;
    }

    /**
     * Verse ids + keys in canonical mushaf order.
     *
     * @return list<array{id: int, verse_key: string}>
     */
    private function getVersesInMushafOrder(): array
    {
        $verses = Verse::query()
            ->select(['id', 'verse_key'])
            ->orderBy('surah_id')
            ->orderBy('verse_number')
            ->get()
            ->map(fn (Verse $verse) => ['id' => $verse->id, 'verse_key' => $verse->verse_key])
            ->all();

        $this->assertVerseCount(count($verses));

        return $verses;
    }

    private function assertVerseCount(int $count): void
    {
        if ($count !== self::EXPECTED_VERSE_COUNT) {
            throw new InvalidArgumentException(sprintf(
                'quran_verses holds %d rows, expected %d — seed verses before importing texts.',
                $count,
                self::EXPECTED_VERSE_COUNT,
            ));
        }
    }

    /**
     * @param  callable(array<int, array<string, mixed>>): int  $upsert
     * @param  list<array<string, mixed>>  $rows
     */
    private function upsertChunked(callable $upsert, array $rows): int
    {
        return DB::transaction(function () use ($upsert, $rows): int {
            $written = 0;

            foreach (array_chunk($rows, self::UPSERT_CHUNK_SIZE) as $chunk) {
                $upsert($chunk);
                $written += count($chunk);
            }

            return $written;
        });
    }
}
