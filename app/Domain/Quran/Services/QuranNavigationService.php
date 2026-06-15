<?php

declare(strict_types=1);

namespace App\Domain\Quran\Services;

use App\Domain\Quran\Models\Surah;
use App\Domain\Quran\Repositories\SurahRepositoryInterface;

/**
 * Resolves free-text search input into direct navigation targets — the way
 * quran.com's search box jumps when you type "2:255", "juz 5", "page 50" or a
 * surah name, instead of running a full-text search.
 *
 * Pure resolution logic; the only persistence touchpoint is the surah
 * repository (for name matching and verse-range validation).
 */
final readonly class QuranNavigationService
{
    private const SURAH_COUNT = 114;

    private const JUZ_COUNT = 30;

    private const HIZB_COUNT = 60;

    private const PAGE_COUNT = 604;

    public function __construct(
        private SurahRepositoryInterface $surahRepository,
    ) {}

    /**
     * Resolve a query into navigation suggestions, most-specific first.
     *
     * @return list<array<string, mixed>>
     */
    public function suggest(string $query, int $limit = 8): array
    {
        $normalized = $this->normalizeDigits(trim($query));

        if ($normalized === '') {
            return [];
        }

        $suggestions = array_filter([
            $this->matchVerseKey($normalized),
            $this->matchDivision($normalized),
            $this->matchSurahNumber($normalized),
        ]);

        foreach ($this->matchSurahNames($normalized, $limit) as $surahSuggestion) {
            $suggestions[] = $surahSuggestion;
        }

        return $this->dedupe($suggestions, $limit);
    }

    /**
     * "2:255" → a specific verse. Rejects an out-of-range verse number when the
     * surah is known; still offers a structurally valid jump when it is not
     * (e.g. an unseeded test database).
     *
     * @return array<string, mixed>|null
     */
    private function matchVerseKey(string $query): ?array
    {
        if (! preg_match('/^(\d{1,3}):(\d{1,3})$/', $query, $matches)) {
            return null;
        }

        $surahNumber = (int) $matches[1];
        $verseNumber = (int) $matches[2];

        if ($surahNumber < 1 || $surahNumber > self::SURAH_COUNT || $verseNumber < 1) {
            return null;
        }

        $surah = $this->surahRepository->getByNumber($surahNumber);

        if ($surah !== null && $verseNumber > $surah->verses_count) {
            return null;
        }

        $verseKey = $surahNumber.':'.$verseNumber;

        return [
            'type' => 'verse',
            'surah_number' => $surahNumber,
            'verse_number' => $verseNumber,
            'verse_key' => $verseKey,
            'label' => $surah !== null
                ? $this->surahName($surah).' — '.$verseKey
                : trim(__('quran.nav.verse').' '.$verseKey),
        ];
    }

    /**
     * "juz 5" / "page 50" / "hizb 12" (Arabic keywords supported).
     *
     * @return array<string, mixed>|null
     */
    private function matchDivision(string $query): ?array
    {
        $divisions = [
            'juz' => ['/^(?:juz|para|جزء)\s*0*(\d{1,2})$/iu', self::JUZ_COUNT],
            'hizb' => ['/^(?:hizb|حزب)\s*0*(\d{1,2})$/iu', self::HIZB_COUNT],
            'page' => ['/^(?:page|p|صفحة|ص)\s*0*(\d{1,3})$/iu', self::PAGE_COUNT],
        ];

        foreach ($divisions as $type => [$pattern, $max]) {
            if (! preg_match($pattern, $query, $matches)) {
                continue;
            }

            $number = (int) $matches[1];

            if ($number < 1 || $number > $max) {
                return null;
            }

            return [
                'type' => $type,
                'number' => $number,
                'label' => trim(__('quran.nav.'.$type).' '.$number),
            ];
        }

        return null;
    }

    /**
     * A bare number 1–114 → that surah.
     *
     * @return array<string, mixed>|null
     */
    private function matchSurahNumber(string $query): ?array
    {
        if (! preg_match('/^0*(\d{1,3})$/', $query, $matches)) {
            return null;
        }

        $number = (int) $matches[1];

        if ($number < 1 || $number > self::SURAH_COUNT) {
            return null;
        }

        $surah = $this->surahRepository->getByNumber($number);

        return [
            'type' => 'surah',
            'surah_number' => $number,
            'label' => $surah !== null
                ? $this->surahName($surah)
                : trim(__('quran.nav.surah').' '.$number),
        ];
    }

    /**
     * Surah-name matches (skipped for pure-number input, handled above).
     *
     * The surahs table is a fixed 114 rows (cached), so matching is done in PHP:
     * stored names carry full Arabic diacritics, an English spelling without a
     * trailing "h" ("Al-Baqara"), and an empty transliteration — none of which a
     * SQL LIKE can match against typical user input. Normalization + bidirectional
     * containment handles diacritics, the "al-"/"surah" prefixes and spelling drift.
     *
     * @return list<array<string, mixed>>
     */
    private function matchSurahNames(string $query, int $limit): array
    {
        if (mb_strlen($query) < 2 || preg_match('/^\d+$/', $query)) {
            return [];
        }

        $needle = $this->normalizeName($query);

        if ($needle === '') {
            return [];
        }

        $results = [];

        foreach ($this->surahRepository->all() as $surah) {
            if (count($results) >= $limit) {
                break;
            }

            if ($this->nameMatches($needle, $surah)) {
                $results[] = [
                    'type' => 'surah',
                    'surah_number' => $surah->revelation_order,
                    'label' => $this->surahName($surah),
                ];
            }
        }

        return $results;
    }

    /**
     * True when the normalized needle relates to any of the surah's names.
     */
    private function nameMatches(string $needle, Surah $surah): bool
    {
        foreach ([$surah->name_ar, $surah->name_en, $surah->transliteration] as $candidate) {
            if ($candidate === null || $candidate === '') {
                continue;
            }

            $haystack = $this->normalizeName($candidate);

            if ($haystack === '') {
                continue;
            }

            // Forward match always; reverse match (query contains the name) only for
            // longer names, so a single-letter surah ("ق") doesn't match every query
            // that merely happens to contain that letter.
            if (str_contains($haystack, $needle)
                || (mb_strlen($haystack) >= 4 && str_contains($needle, $haystack))) {
                return true;
            }
        }

        return false;
    }

    /**
     * Localized "Surah <name>" label, falling back across locales then number.
     */
    private function surahName(Surah $surah): string
    {
        $name = in_array(app()->getLocale(), ['ar', 'ur'], true)
            ? ($surah->name_ar ?: $surah->name_en)
            : ($surah->name_en ?: $surah->name_ar);

        $name = $this->stripSurahPrefix((string) $name);

        return trim(__('quran.nav.surah').' '.($name !== '' ? $name : (string) $surah->revelation_order));
    }

    /**
     * Remove a leading "Surah"/"سورة" qualifier so the localized label isn't
     * doubled (some sources store names already prefixed).
     */
    private function stripSurahPrefix(string $name): string
    {
        $name = trim($name);
        $name = preg_replace('/^(?:surah|sura|sourate|sure)\s+/iu', '', $name) ?? $name;

        $parts = preg_split('/\s+/u', $name, 2);
        if (is_array($parts) && count($parts) === 2) {
            $firstStripped = preg_replace('/[\x{064B}-\x{0652}\x{0670}\x{0640}]/u', '', $parts[0]) ?? $parts[0];
            $firstStripped = strtr($firstStripped, ['ة' => 'ه']);
            if ($firstStripped === 'سوره') {
                return trim($parts[1]);
            }
        }

        return $name;
    }

    /**
     * Collapse a name to a diacritic-, prefix- and spacing-insensitive key for
     * matching: strip Arabic harakat, unify letter variants, drop the surah word
     * and the definite article, keep only letters/digits, and squash doubled
     * letters ("faatiha" → "fatiha").
     */
    private function normalizeName(string $value): string
    {
        $value = mb_strtolower($value);

        // Strip Arabic short vowels, sukun, shadda, superscript alef and tatweel.
        $value = preg_replace('/[\x{064B}-\x{0652}\x{0670}\x{0640}]/u', '', $value) ?? $value;

        // Unify Arabic letter variants.
        $value = strtr($value, [
            'أ' => 'ا', 'إ' => 'ا', 'آ' => 'ا', 'ٱ' => 'ا',
            'ى' => 'ي', 'ئ' => 'ي', 'ؤ' => 'و', 'ة' => 'ه',
        ]);

        // Drop the "surah" qualifier (Latin + Arabic) and the English article.
        $value = preg_replace('/\b(?:surah|sura|sourate|sure|the)\b/u', '', $value) ?? $value;
        $value = str_replace('سوره', '', $value);

        // Keep only letters and digits (drops spaces, hyphens, apostrophes).
        $value = preg_replace('/[^\p{L}\p{N}]+/u', '', $value) ?? $value;

        // Collapse consecutive duplicate characters.
        $value = preg_replace('/(.)\1+/u', '$1', $value) ?? $value;

        // Strip a leading definite article.
        return preg_replace('/^(?:al|ال)/u', '', $value) ?? $value;
    }

    /**
     * Map Arabic-Indic and Eastern-Arabic (Urdu/Persian) digits to ASCII so the
     * parsers above work regardless of the user's keyboard.
     */
    private function normalizeDigits(string $value): string
    {
        $from = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩', '۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
        $to = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9', '0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];

        return str_replace($from, $to, $value);
    }

    /**
     * Drop duplicate targets (same type + identifier) and cap the result set.
     *
     * @param  array<int, array<string, mixed>>  $suggestions
     * @return list<array<string, mixed>>
     */
    private function dedupe(array $suggestions, int $limit): array
    {
        $seen = [];
        $unique = [];

        foreach ($suggestions as $suggestion) {
            $identifier = $suggestion['verse_key']
                ?? $suggestion['surah_number']
                ?? $suggestion['number']
                ?? '';
            $key = $suggestion['type'].':'.$identifier;

            if (isset($seen[$key])) {
                continue;
            }

            $seen[$key] = true;
            $unique[] = $suggestion;

            if (count($unique) >= $limit) {
                break;
            }
        }

        return $unique;
    }
}
