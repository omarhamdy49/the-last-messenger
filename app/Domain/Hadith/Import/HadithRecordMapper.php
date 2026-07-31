<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Import;

use App\Domain\Hadith\Services\ArabicTextNormalizer;

/**
 * Maps one extracted JSON record onto the column shapes the importer persists,
 * per contracts/extraction-matrix.md.
 *
 * Pure: no database access, no side effects. Everything it decides can therefore
 * be asserted from a fixture without a schema.
 */
final class HadithRecordMapper
{
    public function __construct(
        private readonly ArabicTextNormalizer $normalizer,
        private readonly GradeNormalizer $grades,
    ) {}

    /**
     * @param  array<string, mixed>  $book
     * @return array{book_number: string, title: array<string, string>}
     */
    public function mapBook(array $book, string $fallbackNumber): array
    {
        return [
            'book_number' => $this->text($book['number'] ?? null) ?? $fallbackNumber,
            'title' => $this->translatable($book['title'] ?? []),
        ];
    }

    /**
     * @param  array<string, mixed>  $chapter
     * @return array{chapter_number: string, sort_order: int, title: array<string, string>, intro: array<string, string>}
     */
    public function mapChapter(array $chapter, int $sortOrder): array
    {
        // The source prints chapter numbers parenthesised — "(1)". The parens are
        // presentation, and keeping them would put them in every URL and every
        // UNIQUE (book_id, chapter_number) key.
        $number = trim((string) ($this->text($chapter['number'] ?? null) ?? ''), '() ');

        return [
            // Some chapters genuinely carry no number — Bukhari book 2 has one,
            // a Quranic-verse heading. It is stored empty rather than given an
            // invented number; identity comes from sort_order.
            'chapter_number' => $number,
            'sort_order' => (int) ($chapter['sort_order'] ?? $sortOrder),
            'title' => $this->translatable($chapter['title'] ?? []),
            'intro' => $this->translatable($chapter['intro'] ?? []),
        ];
    }

    /**
     * @param  array<string, mixed>  $record
     * @return array{
     *     hadith: array<string, mixed>,
     *     texts: list<array<string, mixed>>,
     *     gradings: list<array<string, mixed>>,
     *     chapter_index: ?int,
     *     chapter_number: ?string
     * }
     */
    public function mapRecord(array $record): array
    {
        $references = $this->parseReferences($record['reference_rows'] ?? []);

        return [
            // Position, not number: a chapter number does not identify a chapter.
            // Older extractions carry no chapter_index; those fall back to the
            // ambiguous number so a stale file still imports, visibly degraded.
            'chapter_index' => is_int($record['chapter_index'] ?? null) ? $record['chapter_index'] : null,
            'chapter_number' => ($n = $this->text($record['chapter_number'] ?? null)) !== null
                ? trim($n, '() ')
                : null,
            'hadith' => [
                'hadith_number' => $this->text($record['hadith_number'] ?? null) ?? '',
                'in_book_number' => $references['in_book_number'],
                'volume_number' => $references['volume_number'],
                // 0 is sunnah.com's "absent" marker, not a real permalink.
                'sunnah_urn' => $this->positiveInt($record['sunnah_urn'] ?? null),
                'sunnah_urn_en' => $this->positiveInt($record['sunnah_urn_en'] ?? null),
            ],
            'texts' => $this->mapTexts($record['texts'] ?? [], $record['source_url'] ?? null),
            'gradings' => $this->mapGradings($record['gradings'] ?? []),
        ];
    }

    /**
     * Every raw string this mapper could not interpret, for the import run.
     *
     * @return list<string>
     */
    public function unmappedGrades(): array
    {
        return $this->grades->unmappedGrades();
    }

    /**
     * @param  array<string, mixed>  $texts
     * @return list<array<string, mixed>>
     */
    private function mapTexts(array $texts, ?string $sourceUrl): array
    {
        $rows = [];

        foreach ($texts as $locale => $text) {
            if (! is_array($text)) {
                continue;
            }

            $body = $this->text($text['body'] ?? null);

            // No row at all rather than an empty one: English is absent for ~7%
            // of records, and an empty body renders as a blank panel (FR-015).
            if ($body === null) {
                continue;
            }

            $rows[] = [
                'locale' => (string) $locale,
                'body' => $body,
                'search_body' => $this->normalizer->forLocale($body, (string) $locale),
                'narrated_by' => $this->text($text['narrated_by'] ?? null),
                'sanad' => $this->text($text['sanad'] ?? null),
                'matn' => $this->text($text['matn'] ?? null),
                'source_url' => $sourceUrl,
            ];
        }

        return $rows;
    }

    /**
     * @param  list<array<string, mixed>>  $gradings
     * @return list<array<string, mixed>>
     */
    private function mapGradings(array $gradings): array
    {
        $rows = [];

        foreach ($gradings as $grading) {
            $raw = $this->text($grading['raw_grade'] ?? null);

            if ($raw === null) {
                continue;
            }

            $locale = $this->text($grading['locale'] ?? null);

            foreach ($this->grades->normalize($raw, $locale) as $claim) {
                $rows[] = [
                    'grade' => $claim['grade'],
                    'scope' => $claim['scope'],
                    'part_label' => $claim['part_label'],
                    'raw_grade' => $raw,
                    'locale' => $locale,
                    'attributed_to' => $this->attribution($raw),
                ];
            }
        }

        return $rows;
    }

    /**
     * The grader named in parentheses — "(Al-Albani)", "(Darussalam)". Part of
     * the verdict string in the source, but a separate fact for us.
     */
    private function attribution(string $raw): ?string
    {
        if (preg_match('/\(([^)]{2,60})\)\s*$/u', trim($raw), $m) !== 1) {
            return null;
        }

        $name = trim($m[1]);

        return $name === '' ? null : $name;
    }

    /**
     * The three reference lines carry the in-book and volume numbers. They are
     * read rather than trusted positionally: the third row is labelled
     * "USC-MSA web (English) reference" for some collections and "English
     * translation" for others, and malik/darimi have no volume at all.
     *
     * @param  list<list<string|null>>  $rows
     * @return array{in_book_number: ?int, volume_number: ?string}
     */
    private function parseReferences(array $rows): array
    {
        $inBook = null;
        $volume = null;

        foreach ($rows as $row) {
            $value = $this->text($row[1] ?? null);

            if ($value === null) {
                continue;
            }

            if ($inBook === null && preg_match('/Hadith\s+(\d+)/i', $value, $m) === 1) {
                $inBook = (int) $m[1];
            }

            if ($volume === null && preg_match('/Vol\.\s*(\d+)/i', $value, $m) === 1) {
                $volume = $m[1];
            }
        }

        return ['in_book_number' => $inBook, 'volume_number' => $volume];
    }

    /**
     * @return array<string, string>
     */
    private function translatable(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        $out = [];

        foreach ($value as $locale => $text) {
            $clean = $this->text($text);

            if ($clean !== null) {
                $out[(string) $locale] = $clean;
            }
        }

        return $out;
    }

    private function text(mixed $value): ?string
    {
        if (! is_string($value) && ! is_int($value)) {
            return null;
        }

        $clean = trim((string) $value);

        return $clean === '' ? null : $clean;
    }

    private function positiveInt(mixed $value): ?int
    {
        return is_numeric($value) && (int) $value > 0 ? (int) $value : null;
    }
}
