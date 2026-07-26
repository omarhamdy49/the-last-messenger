<?php

declare(strict_types=1);

namespace App\Domain\Seerah\Services;

use App\Domain\Shared\Helpers\VersionedCacheKey;

final class SeerahCacheKeys
{
    private const EVENT_PREFIX = 'seerahevent';

    private const MILESTONE_PREFIX = 'seerahmilestone';

    public static function eventBySlug(string $slug): string
    {
        return self::eventKey('slug', compact('slug'));
    }

    public static function featured(int $limit): string
    {
        return self::eventKey('featured', compact('limit'));
    }

    public static function eventsWithLocation(): string
    {
        return self::eventKey('withLocation');
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

    public static function events(string $locale, string $search, string $category, int $page): string
    {
        return self::eventKey('publicEvents', compact('locale', 'search', 'category', 'page'));
    }

    public static function event(string $slug, string $locale): string
    {
        return self::eventKey('publicEvent', compact('slug', 'locale'));
    }

    public static function related(string $slug, string $locale): string
    {
        return self::eventKey('publicRelated', compact('slug', 'locale'));
    }

    /**
     * @param  array<string, mixed>  $parameters
     */
    private static function eventKey(string $name, array $parameters = []): string
    {
        return VersionedCacheKey::make(self::EVENT_PREFIX, $name, $parameters);
    }
}
