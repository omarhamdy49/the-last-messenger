<?php

declare(strict_types=1);

namespace App\Domain\Quran\Services;

use App\Domain\Quran\Enums\ResourceKind;
use App\Domain\Quran\Enums\SectionType;
use Illuminate\Support\Facades\Cache;

/**
 * Centralised cache keys for the Quran domain.
 *
 * Invalidation uses version counters rather than Cache::tags() (file/database cache
 * drivers do not support tags) and never Cache::flush(). Every key is prefixed with
 * the current version of its scope, so bumping a counter orphans the whole scope in
 * one write without touching other modules' caches.
 *
 * Scopes change independently:
 *   - content:   chapters, sections, verses, words, translations, tafsirs, chapter
 *                infos, reader payloads, search
 *   - resources: the catalogue (which translations/tafsirs/reciters are active)
 *   - reciters:  audio files, chapter audio, timestamps
 *   - mushafs:   page layouts
 *   - playlists: playlist listings
 *
 * Deliberately unversioned: the Ask-Quran AI keys are expensive LLM/MCP results
 * that do not go stale when Quran content is edited.
 */
final class QuranCacheKeys
{
    private const CONTENT_VERSION_KEY = 'quran:cache:version:content';

    private const RESOURCES_VERSION_KEY = 'quran:cache:version:resources';

    private const RECITER_VERSION_KEY = 'reciters:version';

    private const MUSHAF_VERSION_KEY = 'quran:cache:version:mushafs';

    private const PLAYLIST_VERSION_KEY = 'playlists:version';

    // -----------------------------------------------------------------------
    // Invalidation
    // -----------------------------------------------------------------------

    public static function flushContent(): void
    {
        self::bump(self::CONTENT_VERSION_KEY);
    }

    /**
     * The catalogue also shapes every reader payload (selected resources), so a
     * catalogue change drops content payloads too.
     */
    public static function flushResources(): void
    {
        self::bump(self::RESOURCES_VERSION_KEY);
        self::bump(self::CONTENT_VERSION_KEY);
    }

    public static function flushReciters(): void
    {
        self::bump(self::RECITER_VERSION_KEY);
    }

    public static function flushMushafs(): void
    {
        self::bump(self::MUSHAF_VERSION_KEY);
    }

    /**
     * Play counts do not call this: they are eventually consistent in listings.
     */
    public static function flushPlaylists(): void
    {
        self::bump(self::PLAYLIST_VERSION_KEY);
    }

    public static function flushAll(): void
    {
        self::flushResources();
        self::flushReciters();
        self::flushMushafs();
        self::flushPlaylists();
    }

    private static function bump(string $versionKey): void
    {
        Cache::forever($versionKey, ((int) Cache::get($versionKey, 0)) + 1);
    }

    private static function scoped(string $versionKey, string $prefix, string $key): string
    {
        return $prefix.':v'.((int) Cache::get($versionKey, 0)).':'.$key;
    }

    private static function content(string $key): string
    {
        return self::scoped(self::CONTENT_VERSION_KEY, 'quran', $key);
    }

    public static function playlistScoped(string $key): string
    {
        return self::scoped(self::PLAYLIST_VERSION_KEY, 'playlists', $key);
    }

    /**
     * One counted play per visitor per playlist per window. Outside the versioned
     * family so flushPlaylists() never resets it.
     */
    public static function playlistPlayDedupe(int $playlistId, string $visitorHash): string
    {
        return "playlist_play_dedupe_{$playlistId}_{$visitorHash}";
    }

    // -----------------------------------------------------------------------
    // Content
    // -----------------------------------------------------------------------

    public static function chapters(string $locale): string
    {
        return self::content("chapters:{$locale}");
    }

    public static function chapter(int $id, string $locale): string
    {
        return self::content("chapter:{$id}:{$locale}");
    }

    public static function sections(SectionType $type): string
    {
        return self::content("sections:{$type->value}");
    }

    public static function readerPayload(string $scope, string $optionsHash, string $locale): string
    {
        return self::content("reader:{$scope}:{$optionsHash}:{$locale}");
    }

    public static function chapterInfo(int $chapter, string $locale, ?int $resourceId): string
    {
        return self::content("chapter_info:{$chapter}:{$locale}:".($resourceId ?? 'auto'));
    }

    public static function verseOfTheDay(string $date, string $locale): string
    {
        return self::content("verse_of_the_day:{$date}:{$locale}");
    }

    public static function search(string $hash): string
    {
        return self::content("search:{$hash}");
    }

    /**
     * Normalized chapter-name => number lookup (Ask-AI answer linking).
     */
    public static function chapterNameIndex(): string
    {
        return self::content('chapter_name_index');
    }

    // -----------------------------------------------------------------------
    // Catalogue
    // -----------------------------------------------------------------------

    public static function resources(ResourceKind $kind, string $locale): string
    {
        return self::scoped(self::RESOURCES_VERSION_KEY, 'quran_resources', "{$kind->value}:{$locale}");
    }

    public static function recitationStyles(): string
    {
        return 'quran:recitation_styles';
    }

    // -----------------------------------------------------------------------
    // Audio
    // -----------------------------------------------------------------------

    public static function chapterAudio(int $chapter): string
    {
        return self::scoped(self::RECITER_VERSION_KEY, 'reciters', "chapter_audio:{$chapter}");
    }

    public static function chapterTimestamps(int $resourceId, int $chapter): string
    {
        return self::scoped(self::RECITER_VERSION_KEY, 'reciters', "timestamps:{$resourceId}:{$chapter}");
    }

    public static function reciterPage(int $resourceId, string $locale): string
    {
        return self::scoped(self::RECITER_VERSION_KEY, 'reciters', "reciter:{$resourceId}:{$locale}");
    }

    /** Player reciter list for one chapter reader page. */
    public static function readerReciters(int $chapter, string $locale): string
    {
        return self::scoped(self::RECITER_VERSION_KEY, 'reciters', "reader:{$chapter}:{$locale}");
    }

    /** Public reciters directory. */
    public static function reciterDirectory(string $locale): string
    {
        return self::scoped(self::RECITER_VERSION_KEY, 'reciters', "directory:{$locale}");
    }

    /** chapter number => juz of its first verse. */
    public static function firstJuzMap(): string
    {
        return self::content('first_juz_map');
    }

    // -----------------------------------------------------------------------
    // Mushafs
    // -----------------------------------------------------------------------

    public static function pageLines(int $mushaf, int $page): string
    {
        return self::scoped(self::MUSHAF_VERSION_KEY, 'quran_mushaf', "lines:{$mushaf}:{$page}");
    }

    public static function chapterInfos(int $chapter): string
    {
        return self::content("chapter_infos:{$chapter}");
    }

    public static function chapterLines(int $mushaf, int $chapter): string
    {
        return self::scoped(self::MUSHAF_VERSION_KEY, 'quran_mushaf', "chapter_lines:{$mushaf}:{$chapter}");
    }

    // -----------------------------------------------------------------------
    // Ask Quran AI (unversioned)
    // -----------------------------------------------------------------------

    public static function aiGroundingRules(): string
    {
        return 'quran:ai:grounding_rules';
    }

    public static function aiAnswer(string $questionHash, string $locale): string
    {
        return "quran:ai:answer:{$locale}:{$questionHash}";
    }
}
