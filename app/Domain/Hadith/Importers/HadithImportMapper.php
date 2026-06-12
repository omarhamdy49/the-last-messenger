<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Importers;

use InvalidArgumentException;

/**
 * HadithImportMapper
 *
 * Responsible for converting the raw transformation envelope produced by
 * JsonDataTransformer::transform() into a pair of typed payload structures
 * that HadithImportPersister can act on without knowing anything about the
 * original JSON format.
 *
 * Two output shapes are defined, matching the two import paths:
 *
 *   single_hadith   → map() returns ['type' => 'single_hadith',   'records'    => [...]]
 *   collection_based → map() returns ['type' => 'collection_based', 'collection' => [...],
 *                                      'book' => [...], 'chapters' => [...], 'hadiths' => [...]]
 *
 * The mapper does NOT touch the database. It only normalises field names and
 * provides safe defaults for missing optional fields.
 */
class HadithImportMapper
{
    // =========================================================================
    // PUBLIC API
    // =========================================================================

    /**
     * Map a transformer envelope into a persister-ready payload.
     *
     * @param  array  $transformed  Envelope from JsonDataTransformer::transform().
     * @param  string  $format  Value of $transformed['type'] — passed explicitly so
     *                          callers do not have to read into the array themselves.
     * @return array Persister-ready payload.
     *
     * @throws InvalidArgumentException When the format is not supported.
     */
    public function map(array $transformed, string $format): array
    {
        return match ($format) {
            'single_hadith' => $this->mapSingleHadith($transformed),
            'collection_based' => $this->mapCollectionBased($transformed),
            default => throw new InvalidArgumentException("Unknown JSON format type: {$format}"),
        };
    }

    // =========================================================================
    // FORMAT-SPECIFIC MAPPING
    // =========================================================================

    /**
     * Map a single-hadith envelope.
     *
     * Ensures every record carries all expected keys with safe defaults so that
     * the persister never has to use isset() for optional metadata arrays.
     */
    private function mapSingleHadith(array $transformed): array
    {
        $records = $transformed['records'] ?? [];

        $mapped = array_map(
            fn (array $record): array => $this->normaliseSingleRecord($record),
            $records
        );

        return [
            'type' => 'single_hadith',
            'records' => $mapped,
        ];
    }

    /**
     * Map a collection-based envelope.
     *
     * Ensures the top-level collection/book/chapters/hadiths keys are always
     * present and that each hadith entry carries a 'translations' list.
     */
    private function mapCollectionBased(array $transformed): array
    {
        $hadiths = array_map(
            fn (array $entry): array => [
                'hadith' => $entry['hadith'] ?? [],
                'translations' => $entry['translations'] ?? [],
                'sources' => $entry['sources'] ?? [],
                'source_file' => $entry['source_file'] ?? null,
            ],
            $transformed['hadiths'] ?? []
        );

        return [
            'type' => 'collection_based',
            'collection' => $transformed['collection'] ?? [],
            'book' => $transformed['book'] ?? [],
            'chapters' => $transformed['chapters'] ?? [],
            'hadiths' => $hadiths,
        ];
    }

    // =========================================================================
    // SINGLE-HADITH RECORD NORMALISATION
    // =========================================================================

    /**
     * Guarantee that every key the persister expects exists in a single-hadith
     * record, providing empty arrays/null for optional metadata.
     */
    private function normaliseSingleRecord(array $record): array
    {
        return [
            'collection' => $record['collection'] ?? [],
            'book' => $record['book'] ?? [],
            'chapter' => $record['chapter'] ?? null,
            'companion' => $record['companion'] ?? null,
            'hadith' => $record['hadith'] ?? [],
            'historical_context' => $record['historical_context'] ?? null,
            'translations' => $record['translations'] ?? [],
            'narrators' => $record['narrators'] ?? [],
            'themes' => $record['themes'] ?? [],
            'keywords' => $record['keywords'] ?? [],
            'grading' => $record['grading'] ?? [],
            'scholarly_comments' => $record['scholarly_comments'] ?? [],
            'related_verses' => $record['related_verses'] ?? [],
            'applications' => $record['applications'] ?? [],
            'fiqh_rulings' => $record['fiqh_rulings'] ?? [],
            'sources' => $record['sources'] ?? [],
            'source_file' => $record['source_file'] ?? null,
        ];
    }
}
