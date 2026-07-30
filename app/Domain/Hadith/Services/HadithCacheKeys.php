<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Services;

use App\Domain\Shared\Helpers\VersionedCacheKey;

/**
 * Every Hadith cache key is built here. Inline key strings drift: two call sites
 * writing and reading slightly different strings is a cache that silently never
 * hits, and nothing fails loudly enough to notice.
 */
final class HadithCacheKeys
{
    private const PREFIX = 'hadith';

    public static function hub(string $locale): string
    {
        return self::key('hub', compact('locale'));
    }

    public static function collection(string $code, string $locale): string
    {
        return self::key('collection', compact('code', 'locale'));
    }

    public static function books(string $code, string $locale): string
    {
        return self::key('books', compact('code', 'locale'));
    }

    public static function book(string $code, string $bookNumber, string $locale): string
    {
        return self::key('book', compact('code', 'bookNumber', 'locale'));
    }

    public static function chapters(string $code, string $bookNumber, string $locale): string
    {
        return self::key('chapters', compact('code', 'bookNumber', 'locale'));
    }

    public static function canonical(string $code, string $hadithNumber, string $locale): string
    {
        return self::key('canonical', compact('code', 'hadithNumber', 'locale'));
    }

    public static function stats(): string
    {
        return self::key('stats');
    }

    /**
     * Search results are deliberately absent: the query space is unbounded, so
     * caching it fills the store with single-use entries.
     *
     * @param  array<string, mixed>  $parameters
     */
    private static function key(string $method, array $parameters = []): string
    {
        return VersionedCacheKey::make(self::PREFIX, $method, $parameters);
    }
}
