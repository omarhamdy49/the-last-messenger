<?php

declare(strict_types=1);

namespace App\Domain\Quran\Services;

/**
 * Centralised cache key definitions for the Quran domain.
 *
 * Naming convention: {module}:{entity}:{identifier}:{variant}
 *
 * All keys must be kept in sync with the repositories that create them so
 * that admin controllers can invalidate the right entries after writes.
 */
final class QuranCacheKeys
{
    // -----------------------------------------------------------------------
    // Surah keys (SurahRepository)
    // -----------------------------------------------------------------------

    public static function surahAll(): string
    {
        return 'quran:surahs:all';
    }

    public static function surahByNumber(int $number): string
    {
        return "quran:surah:number:{$number}";
    }

    public static function surahsByRevelationPlace(string $place): string
    {
        return "quran:surahs:revelation:{$place}";
    }

    public static function surahsByJuz(int $juz): string
    {
        return "quran:surahs:juz:{$juz}";
    }

    public static function surahCountByRevelation(string $place): string
    {
        return "quran:surahs:count:{$place}";
    }

    public static function surahTotalVerses(): string
    {
        return 'quran:total:verses';
    }

    public static function surahAverageVerses(): string
    {
        return 'quran:surahs:avg:verses';
    }

    // -----------------------------------------------------------------------
    // Verse keys (VerseRepository)
    // -----------------------------------------------------------------------

    public static function versesBySurah(int $surahId): string
    {
        return "quran:verses:surah:{$surahId}";
    }

    public static function verseBySurahAndNumber(int $surahNumber, int $verseNumber): string
    {
        return "quran:verse:{$surahNumber}:{$verseNumber}";
    }

    public static function versesByPage(int $page): string
    {
        return "quran:verses:page:{$page}";
    }

    public static function sajdaVerses(): string
    {
        return 'quran:verses:sajda';
    }

    public static function sajdaVersesCount(): string
    {
        return 'quran:verses:sajda:count';
    }

    // -----------------------------------------------------------------------
    // Tafseer keys (TafseerRepository)
    // -----------------------------------------------------------------------

    public static function tafseerByVerseReference(string $verseReference): string
    {
        return "quran:tafseer:verse:{$verseReference}";
    }

    public static function tafseerSources(): string
    {
        return 'quran:tafseer:sources';
    }

    public static function tafseerUniqueSurahs(): string
    {
        return 'quran:tafseer:unique:surahs';
    }

    // -----------------------------------------------------------------------
    // Shared / helper keys
    // -----------------------------------------------------------------------

    /**
     * Selector cache used by admin controllers (VerseController, TafseerController)
     * when populating the Surah dropdown.
     */
    public static function surahSelector(): string
    {
        return 'quran:surahs:selector';
    }

    // -----------------------------------------------------------------------
    // Ask Quran AI keys (DeepSeek + quran.ai MCP)
    // -----------------------------------------------------------------------

    /**
     * The quran.ai MCP grounding rules + nonce, fetched once and shared across
     * all inquiries so the ~2.5K rules block is paid roughly once.
     */
    public static function aiGroundingRules(): string
    {
        return 'quran:ai:grounding_rules';
    }

    /**
     * Cross-user answer cache. Keyed by the normalized-question hash + locale so
     * an identical question (any user) is served free, without an LLM call.
     */
    public static function aiAnswer(string $questionHash, string $locale): string
    {
        return "quran:ai:answer:{$locale}:{$questionHash}";
    }

    /**
     * Normalized surah-name => number lookup, built once from the surahs table
     * and used to linkify verse references written by name in AI answers.
     */
    public static function surahNameIndex(): string
    {
        return 'quran:surah_name_index';
    }
}
