<?php

declare(strict_types=1);

namespace App\Domain\Quran\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Powers the public "Ask the Quran" inquiry search.
 *
 * Sends a free-text inquiry to the authless quran.com v4 search endpoint — the
 * same canonical corpus the quran.ai MCP wraps — and maps the matching verses
 * (Arabic + a single localized translation) into a flat, view-ready shape.
 *
 * No LLM and no MCP handshake are involved: this is keyword/semantic verse
 * retrieval against canonical scripture, so results are grounded by construction.
 */
final class QuranInquirySearchService
{
    /** Locales the app ships; mapped 1:1 to quran.com translation language codes. */
    private const SUPPORTED_LOCALES = ['ar', 'en', 'fr', 'tr', 'ur'];

    private const CACHE_TTL_SECONDS = 86400; // 24h — canonical results are stable.

    private const REQUEST_TIMEOUT_SECONDS = 8;

    /**
     * Run an inquiry and return matching verses.
     *
     * @return array{
     *     query: string,
     *     total: int,
     *     translationName: ?string,
     *     results: list<array{verseKey:string, surah:int, ayah:int, arabic:string, translation:?string, url:string}>
     * }
     *
     * @throws \RuntimeException when the upstream search is unreachable or errors.
     */
    public function search(string $query, string $locale = 'ar', int $limit = 20): array
    {
        $query = trim($query);
        $locale = in_array($locale, self::SUPPORTED_LOCALES, true) ? $locale : 'ar';
        $limit = max(1, min($limit, 30));

        if (mb_strlen($query) < 2) {
            return ['query' => $query, 'total' => 0, 'translationName' => null, 'results' => []];
        }

        $cacheKey = sprintf('quran_inquiry:%s:%d:%s', $locale, $limit, md5($query));

        return Cache::remember($cacheKey, self::CACHE_TTL_SECONDS, function () use ($query, $locale, $limit): array {
            return $this->fetch($query, $locale, $limit);
        });
    }

    /**
     * @return array{query:string, total:int, translationName:?string, results:list<array<string,mixed>>}
     */
    private function fetch(string $query, string $locale, int $limit): array
    {
        $normalized = $this->normalizeQuery($query);
        $result = $this->requestSearch($normalized, $locale, $limit);

        // Arabic recall: quran.com matches the literal verse text, so "الصبر" finds
        // nothing while "صبر" does. If an Arabic query carrying the definite article
        // (ال) returns no verses, retry once without it before giving up.
        if ($result['total'] === 0 && preg_match('/^ال.{2,}/u', $normalized) === 1) {
            $fallback = $this->requestSearch(mb_substr($normalized, 2), $locale, $limit);
            if ($fallback['total'] > 0) {
                $fallback['query'] = $query;

                return $fallback;
            }
        }

        $result['query'] = $query;

        return $result;
    }

    /**
     * Single quran.com search round-trip + mapping.
     *
     * @return array{query:string, total:int, translationName:?string, results:list<array<string,mixed>>}
     */
    private function requestSearch(string $query, string $locale, int $limit): array
    {
        $baseUrl = rtrim((string) config('services.quran.public_api_url'), '/');

        $params = [
            'q' => $query,
            'size' => $limit,
            'page' => 1,
        ];
        // quran.com search returns HTTP 204 (empty) when language=ar; for Arabic we
        // omit it and match against the Arabic verse text directly. For the other
        // locales the language code selects the translation edition to return.
        if ($locale !== 'ar') {
            $params['language'] = $locale;
        }

        try {
            $response = Http::timeout(self::REQUEST_TIMEOUT_SECONDS)
                ->acceptJson()
                ->retry(2, 200)
                ->get("{$baseUrl}/search", $params);
        } catch (\Throwable $e) {
            Log::warning('Quran inquiry search failed', ['query' => $query, 'error' => $e->getMessage()]);
            throw new \RuntimeException('quran_inquiry_unreachable', previous: $e);
        }

        if ($response->failed()) {
            Log::warning('Quran inquiry search non-200', ['query' => $query, 'status' => $response->status()]);
            throw new \RuntimeException('quran_inquiry_failed');
        }

        $payload = $response->json('search') ?? [];
        $results = [];
        $translationName = null;

        foreach ($payload['results'] ?? [] as $row) {
            $verseKey = (string) ($row['verse_key'] ?? '');
            if (! str_contains($verseKey, ':')) {
                continue;
            }
            [$surah, $ayah] = array_map('intval', explode(':', $verseKey, 2));

            // For Arabic we requested no translation edition, so any default the API
            // returns would be a mismatched English line under Arabic — skip it.
            $translation = null;
            if ($locale !== 'ar' && ! empty($row['translations'][0]['text'])) {
                // quran.com wraps matched terms in <em>; strip markup before display.
                $translation = trim(html_entity_decode(strip_tags((string) $row['translations'][0]['text'])));
                $translationName ??= $row['translations'][0]['name'] ?? null;
            }

            $results[] = [
                'verseKey' => $verseKey,
                'surah' => $surah,
                'ayah' => $ayah,
                'arabic' => trim((string) ($row['text'] ?? '')),
                'translation' => $translation !== '' ? $translation : null,
                'url' => url("/quran/{$surah}")."#v{$ayah}",
            ];
        }

        return [
            'query' => $query,
            'total' => (int) ($payload['total_results'] ?? count($results)),
            'translationName' => $translationName,
            'results' => $results,
        ];
    }

    /**
     * Strip Arabic diacritics (harakat) and tatweel so an Arabic inquiry matches
     * the canonical verse text, which quran.com indexes without them. No-op for
     * Latin scripts.
     */
    private function normalizeQuery(string $query): string
    {
        // Harakat U+064B–U+0652, superscript alef U+0670, and tatweel U+0640.
        $stripped = preg_replace('/[\x{064B}-\x{0652}\x{0670}\x{0640}]/u', '', $query);

        return trim($stripped ?? $query);
    }
}
