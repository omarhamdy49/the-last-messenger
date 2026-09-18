<?php

declare(strict_types=1);

namespace App\Domain\Quran\Services;

/**
 * Powers the public "Ask the Quran" inquiry search.
 *
 * Since 006 this is a local search: the whole corpus and its translations live in
 * our own tables, so the inquiry runs against `SearchService` instead of the
 * authless quran.com endpoint it used to call at request time. Results are still
 * grounded by construction — this is keyword retrieval over canonical scripture,
 * with no LLM and no MCP handshake involved.
 */
final class QuranInquirySearchService
{
    /** Locales the app ships; a translation resource is picked per locale. */
    private const SUPPORTED_LOCALES = ['ar', 'en', 'fr', 'tr', 'ur'];

    public function __construct(
        private readonly SearchService $search,
        private readonly ReaderService $reader,
    ) {}

    /**
     * Run an inquiry and return matching verses.
     *
     * @return array{
     *     query: string,
     *     total: int,
     *     translationName: ?string,
     *     results: list<array{verseKey:string, surah:int, ayah:int, arabic:string, translation:?string, url:string}>
     * }
     */
    public function search(string $query, string $locale = 'ar', int $limit = 20): array
    {
        $query = trim($query);
        $locale = in_array($locale, self::SUPPORTED_LOCALES, true) ? $locale : 'ar';
        $limit = max(1, min($limit, 30));

        if (mb_strlen($query) < 2) {
            return ['query' => $query, 'total' => 0, 'translationName' => null, 'results' => []];
        }

        $result = $this->run($this->normalizeQuery($query), $locale, $limit);

        // Arabic recall: the index holds the literal verse text, so "الصبر" finds
        // nothing while "صبر" does. If an Arabic query carrying the definite article
        // (ال) returns no verses, retry once without it before giving up.
        if ($result['total'] === 0 && preg_match('/^ال.{2,}/u', $this->normalizeQuery($query)) === 1) {
            $fallback = $this->run(mb_substr($this->normalizeQuery($query), 2), $locale, $limit);
            if ($fallback['total'] > 0) {
                $result = $fallback;
            }
        }

        $result['query'] = $query;

        return $result;
    }

    /**
     * @return array{query:string, total:int, translationName:?string, results:list<array<string,mixed>>}
     */
    private function run(string $query, string $locale, int $limit): array
    {
        // Arabic readers get the verse text itself, so no translation is attached;
        // every other locale gets the reader's default translation, which is also
        // what SearchService falls back to, so the name always matches the text.
        $edition = $locale === 'ar' ? null : $this->reader->defaultTranslation($locale);

        $found = $this->search->advanced(
            query: $query,
            translationIds: $edition === null ? [] : [$edition->id],
            exact: false,
            highlight: false,
            page: 1,
            size: $limit,
            locale: $locale,
        );

        $results = [];
        foreach ($found['verses'] as $row) {
            $translation = $locale === 'ar' || $row['translation'] === null
                ? null
                : trim(html_entity_decode(strip_tags((string) $row['translation'])));

            $results[] = [
                'verseKey' => (string) $row['verse_key'],
                'surah' => (int) $row['chapter'],
                'ayah' => (int) $row['verse'],
                'arabic' => trim((string) $row['text']),
                'translation' => ($translation ?? '') !== '' ? $translation : null,
                'url' => url('/quran/'.(int) $row['chapter']).'#v'.(int) $row['verse'],
            ];
        }

        return [
            'query' => $query,
            'total' => (int) $found['pagination']['total_records'],
            'translationName' => $edition?->displayName($locale),
            'results' => $results,
        ];
    }

    /**
     * Strip Arabic diacritics (harakat) and tatweel so an Arabic inquiry matches
     * the canonical verse text, which is indexed without them. No-op for Latin.
     */
    private function normalizeQuery(string $query): string
    {
        // Harakat U+064B–U+0652, superscript alef U+0670, and tatweel U+0640.
        $stripped = preg_replace('/[\x{064B}-\x{0652}\x{0670}\x{0640}]/u', '', $query);

        return trim($stripped ?? $query);
    }
}
