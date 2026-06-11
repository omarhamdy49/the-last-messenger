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

    public static function programsRow(string $category, int $limit): string
    {
        return "media_programs_row_{$category}_{$limit}";
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

    public static function stats(): string
    {
        return 'media_dashboard_stats';
    }
}
