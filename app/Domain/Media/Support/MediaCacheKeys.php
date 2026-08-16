<?php

declare(strict_types=1);

namespace App\Domain\Media\Support;

/**
 * Centralises every cache key used by the Media domain.
 * Controllers and repositories must never use inline strings.
 */
final class MediaCacheKeys
{
    public static function programBySlug(string $slug): string
    {
        return "media_program_slug_{$slug}";
    }

    public static function programFeatured(int $limit): string
    {
        return "media_programs_featured_{$limit}";
    }

    public static function programTrending(int $limit): string
    {
        return "media_programs_trending_{$limit}";
    }

    public static function programsByCategory(string $category): string
    {
        return "media_programs_category_{$category}";
    }

    public static function programsHero(int $limit): string
    {
        return "media_programs_hero_{$limit}";
    }

    public static function programsTrendingFlagged(int $limit): string
    {
        return "media_programs_trending_flagged_{$limit}";
    }

    public static function programsTrendingByScore(int $limit): string
    {
        return "media_programs_trending_score_{$limit}";
    }

    public static function programsRow(string $category, int $limit): string
    {
        return "media_programs_row_{$category}_{$limit}";
    }

    /**
     * Grouped category rows fetched in a single query. The key folds in the
     * exact category set + per-row cap so it busts when either changes.
     *
     * @param  list<string>  $categoryKeys
     */
    public static function programsGroupedByCategories(array $categoryKeys, int $perCategory): string
    {
        return 'media_programs_grouped_'.md5(implode(',', $categoryKeys))."_{$perCategory}";
    }

    public static function episodeByProgramAndNumber(int $programId, int $season, int $number): string
    {
        return "media_episode_{$programId}_s{$season}_e{$number}";
    }

    public static function episodesByProgram(int $programId): string
    {
        return "media_episodes_program_{$programId}";
    }

    public static function featuredCategoryKeys(): string
    {
        return 'media_featured_category_keys';
    }

    public static function liveChannelsRow(int $limit): string
    {
        return "media_live_channels_row_{$limit}";
    }

    public static function liveChannelBySlug(string $slug): string
    {
        return "media_live_channel_{$slug}";
    }

    public static function liveChannelFirst(): string
    {
        return 'media_live_channel_first';
    }

    public static function stats(): string
    {
        return 'media_dashboard_stats';
    }

    /**
     * Per-visitor view-dedupe flag (TTL-expiring, set via Cache::add).
     * Not cached content — deliberately absent from clearCacheForModel().
     */
    public static function viewDedupe(string $type, int $id, string $visitorHash): string
    {
        return "media_view_dedupe_{$type}_{$id}_{$visitorHash}";
    }
}
