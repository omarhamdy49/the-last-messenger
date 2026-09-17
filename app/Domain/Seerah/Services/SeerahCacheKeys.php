<?php

declare(strict_types=1);

namespace App\Domain\Seerah\Services;

use App\Domain\Shared\Helpers\VersionedCacheKey;

final class SeerahCacheKeys
{
    private const EVENT_PREFIX = 'seerahevent';

    private const MILESTONE_PREFIX = 'seerahmilestone';

    public static function eventBySlug(string $slug, string $locale): string
    {
        return self::eventKey('slug', compact('slug', 'locale'));
    }

    public static function eventsWithLocation(string $locale): string
    {
        return self::eventKey('withLocation', compact('locale'));
    }

    public static function journey(string $locale): string
    {
        return self::eventKey('publicJourney', [
            'locale' => $locale,
            'milestoneVersion' => VersionedCacheKey::version(self::MILESTONE_PREFIX),
        ]);
    }

    public static function journeySource(): string
    {
        return self::eventKey('journeySource', [
            'milestoneVersion' => VersionedCacheKey::version(self::MILESTONE_PREFIX),
        ]);
    }

    /**
     * Lazily-fetched events of one journey milestone (public JSON endpoint).
     */
    public static function journeyMilestoneEvents(string $slug, string $locale): string
    {
        return self::eventKey('journeyMilestoneEvents', [
            'slug' => $slug,
            'locale' => $locale,
            'milestoneVersion' => VersionedCacheKey::version(self::MILESTONE_PREFIX),
        ]);
    }

    /**
     * Lazily-fetched page of the flat "articles" feed (public JSON endpoint).
     */
    public static function articlesFeed(string $locale, int $page): string
    {
        return self::eventKey('articlesFeed', compact('locale', 'page'));
    }

    /**
     * Every filter has to be part of the key — leave one out and two different
     * filter sets share a cached page.
     *
     * @param  array<string, mixed>  $filters
     */
    public static function events(string $locale, array $filters, int $page): string
    {
        ksort($filters);

        return self::eventKey('publicEvents', ['locale' => $locale, 'page' => $page] + $filters);
    }

    public static function event(string $slug, string $locale): string
    {
        return self::eventKey('publicEvent', compact('slug', 'locale'));
    }

    public static function related(string $slug, string $locale): string
    {
        return self::eventKey('publicRelated', compact('slug', 'locale'));
    }

    public static function invalidateEvents(): void
    {
        VersionedCacheKey::bump(self::EVENT_PREFIX);
    }

    /**
     * Bumping the milestone version also rolls every journey key, since journey()
     * embeds that version in its parameters.
     */
    public static function invalidateMilestones(): void
    {
        VersionedCacheKey::bump(self::MILESTONE_PREFIX);
    }

    /**
     * @param  array<string, mixed>  $parameters
     */
    private static function eventKey(string $name, array $parameters = []): string
    {
        return VersionedCacheKey::make(self::EVENT_PREFIX, $name, $parameters);
    }
}
