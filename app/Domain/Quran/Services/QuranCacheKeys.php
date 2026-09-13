<?php

declare(strict_types=1);

namespace App\Domain\Quran\Services;

use Illuminate\Support\Facades\Cache;

/**
 * Centralised cache key definitions for the Quran domain.
 *
 * Naming convention: {module}:{entity}:{identifier}:{variant}
 *
 * All keys must be kept in sync with the repositories that create them so
 * that admin controllers can invalidate the right entries after writes.
 *
 * Invalidation uses version counters rather than Cache::tags(), which requires
 * Redis/Memcached and is unavailable on the file/database drivers this app
 * runs on. Every key below is prefixed with the current version of its scope,
 * so bumping the counter orphans the whole scope in one write — without
 * touching any other module's cache (which Cache::flush() used to destroy).
 *
 * Two scopes exist because they change independently:
 *   - content:  surahs, verses, translations, tafsir, reader page payloads
 *   - reciters: reciters, recitations, audio URL maps
 *
 * Deliberately unversioned: the Ask-Quran AI keys (aiGroundingRules, aiAnswer)
 * are expensive LLM/MCP results that do not go stale when Quran content is
 * edited, so content edits must not evict them.
 */
final class QuranCacheKeys
{
    private const CONTENT_VERSION_KEY = 'quran:cache:version:content';

    /** Kept as-is: ReciterRepository has used this counter since before the scopes existed. */
    private const RECITER_VERSION_KEY = 'reciters:version';

    /** Playlist listings (gallery, featured, home section, profile tab). */
    private const PLAYLIST_VERSION_KEY = 'playlists:version';

    // -----------------------------------------------------------------------
    // Invalidation
    // -----------------------------------------------------------------------

    /**
     * Invalidate every cached surah/verse/translation/tafsir/reader payload.
     */
    public static function flushContent(): void
    {
        // Version-orphaned entries normally age out via their TTL, but the surah
        // name index is stored with rememberForever — forget it explicitly so a
        // bump does not leak a permanent row per flush.
        Cache::forget(self::surahNameIndex());

        self::bump(self::CONTENT_VERSION_KEY);
    }

    /**
     * Invalidate every cached reciter/recitation/audio payload.
     */
    public static function flushReciters(): void
    {
        self::bump(self::RECITER_VERSION_KEY);
    }

    /**
     * Invalidate every cached playlist listing. Play counts do not call this:
     * they are eventually consistent in listings.
     */
    public static function flushPlaylists(): void
    {
        self::bump(self::PLAYLIST_VERSION_KEY);
    }

    public static function contentVersion(): int
    {
        return (int) Cache::get(self::CONTENT_VERSION_KEY, 0);
    }

    public static function reciterVersion(): int
    {
        return (int) Cache::get(self::RECITER_VERSION_KEY, 0);
    }

    private static function bump(string $versionKey): void
    {
        Cache::forever($versionKey, ((int) Cache::get($versionKey, 0)) + 1);
    }

    /**
     * Prefix a key with the current content version.
     */
    private static function content(string $key): string
    {
        return 'quran:v'.self::contentVersion().':'.$key;
    }

    /**
     * Prefix a key with the current reciter version.
     */
    public static function reciterScoped(string $key): string
    {
        return 'reciters:v'.self::reciterVersion().':'.$key;
    }

    /**
     * Prefix a key with the current playlist version.
     */
    public static function playlistScoped(string $key): string
    {
        return 'playlists:v'.((int) Cache::get(self::PLAYLIST_VERSION_KEY, 0)).':'.$key;
    }

    // -----------------------------------------------------------------------
    // Surah keys (SurahRepository)
    // -----------------------------------------------------------------------

    public static function surahAll(): string
    {
        return self::content('surahs:all');
    }

    public static function surahByNumber(int $number): string
    {
        return self::content("surah:number:{$number}");
    }

    public static function surahsByRevelationPlace(string $place): string
    {
        return self::content("surahs:revelation:{$place}");
    }

    public static function surahsByJuz(int $juz): string
    {
        return self::content("surahs:juz:{$juz}");
    }

    public static function surahCountByRevelation(string $place): string
    {
        return self::content("surahs:count:{$place}");
    }

    public static function surahTotalVerses(): string
    {
        return self::content('total:verses');
    }

    public static function surahAverageVerses(): string
    {
        return self::content('surahs:avg:verses');
    }

    // -----------------------------------------------------------------------
    // Verse keys (VerseRepository)
    // -----------------------------------------------------------------------

    public static function versesBySurah(int $surahId): string
    {
        return self::content("verses:surah:{$surahId}");
    }

    public static function verseBySurahAndNumber(int $surahNumber, int $verseNumber): string
    {
        return self::content("verse:{$surahNumber}:{$verseNumber}");
    }

    public static function versesByPage(int $page): string
    {
        return self::content("verses:page:{$page}");
    }

    public static function sajdaVerses(): string
    {
        return self::content('verses:sajda');
    }

    /**
     * Printed-mushaf word layout for a surah (VerseRepository), ordered by
     * page/line/position for line-by-line rendering on the public reader.
     */
    public static function mushafWordsBySurah(int $surahNumber): string
    {
        return self::content("mushaf:words:surah:{$surahNumber}");
    }

    public static function sajdaVersesCount(): string
    {
        return self::content('verses:sajda:count');
    }

    /**
     * Active translation editions used by the reader's edition selector
     * (VerseRepository::getActiveTranslationResources).
     */
    public static function activeTranslationResources(): string
    {
        return self::content('translation_resources:active');
    }

    /**
     * One translation edition's text for a whole surah (VerseRepository).
     */
    public static function surahTranslationByResource(int $surahNumber, int $resourceId): string
    {
        return self::content("surah_translation:{$surahNumber}:{$resourceId}");
    }

    // -----------------------------------------------------------------------
    // Tafseer keys (TafseerRepository)
    // -----------------------------------------------------------------------

    public static function tafseerByVerseReference(string $verseReference): string
    {
        return self::content("tafseer:verse:{$verseReference}");
    }

    public static function tafseerSources(): string
    {
        return self::content('tafseer:sources');
    }

    public static function tafseerUniqueSurahs(): string
    {
        return self::content('tafseer:unique:surahs');
    }

    /**
     * Active tafsir sources used by the reader's source selector
     * (TafseerRepository::getActiveTafsirResources).
     */
    public static function activeTafsirResources(): string
    {
        return self::content('tafsir_resources:active');
    }

    // -----------------------------------------------------------------------
    // Public reader page payloads (Site\QuranController)
    // -----------------------------------------------------------------------

    public static function publicSurahList(string $locale): string
    {
        return self::content("public:index:{$locale}");
    }

    public static function publicSurahFull(int $surahNumber, string $locale): string
    {
        return self::content("public:surah_full:{$surahNumber}:{$locale}");
    }

    public static function publicJuz(int $juz, string $locale, int $page, int $perPage): string
    {
        return self::content("public:juz:{$juz}:{$locale}:{$page}:{$perPage}");
    }

    public static function publicHizb(int $hizb, string $locale, int $page, int $perPage): string
    {
        return self::content("public:hizb:{$hizb}:{$locale}:{$page}:{$perPage}");
    }

    public static function publicMushafPage(int $page, string $locale): string
    {
        return self::content("public:mushaf_page:{$page}:{$locale}");
    }

    public static function publicAsbabSurahs(string $locale): string
    {
        return self::content("public:asbab_surahs:{$locale}");
    }

    // -----------------------------------------------------------------------
    // Reciter / recitation payloads (reciter-scoped version)
    // -----------------------------------------------------------------------

    public static function publicRecitersAudioList(): string
    {
        return self::reciterScoped('public:reciters_audio_list');
    }

    public static function publicReciters(string $locale): string
    {
        return self::reciterScoped("public:reciters:{$locale}");
    }

    public static function publicSurahAudioUrls(int $surahNumber): string
    {
        return self::reciterScoped("public:surah_audio_urls:{$surahNumber}");
    }

    /**
     * Per-ayah timings for one surah of one reciter's recording
     * (AyahTimingService). Reciter-scoped: the timings describe a recording, so
     * a reciter edit must drop them and a content edit must not.
     */
    public static function surahAyahTimings(int $surahNumber, int $reciterId): string
    {
        return self::reciterScoped("public:surah_timings:{$surahNumber}:{$reciterId}");
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
        return self::content('surahs:selector');
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
        return self::content('surah_name_index');
    }
}
