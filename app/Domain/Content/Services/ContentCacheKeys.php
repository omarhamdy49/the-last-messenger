<?php

declare(strict_types=1);

namespace App\Domain\Content\Services;

use App\Domain\Content\Enums\MenuLocation;
use App\Domain\Shared\Models\Language;

/**
 * Centralised cache key definitions for the Content domain.
 *
 * Naming convention: {module}:{entity}:{identifier}:{variant}
 * (mirrors App\Domain\Quran\Services\QuranCacheKeys).
 *
 * All keys must be kept in sync with the services that create them so
 * that writes can invalidate the right entries.
 */
final class ContentCacheKeys
{
    /**
     * Locales the public site ships with. Flush targets are the union of
     * this list and the active languages table, so per-locale keys are
     * invalidated even if a locale was deactivated after being cached.
     *
     * @var array<int, string>
     */
    private const SUPPORTED_LOCALES = ['ar', 'en', 'fr', 'tr', 'ur'];

    // -----------------------------------------------------------------------
    // Site settings (SiteSettingsService)
    // -----------------------------------------------------------------------

    public static function settingsAll(): string
    {
        return 'content:settings:all';
    }

    // -----------------------------------------------------------------------
    // Menus (MenuService)
    // -----------------------------------------------------------------------

    public static function menuTree(string $location, string $locale): string
    {
        return "content:menu:{$location}:{$locale}";
    }

    /**
     * Every menu tree key across all locations and flushable locales.
     *
     * @return array<int, string>
     */
    public static function allMenuTreeKeys(): array
    {
        $keys = [];
        foreach (MenuLocation::values() as $location) {
            foreach (self::flushableLocales() as $locale) {
                $keys[] = self::menuTree($location, $locale);
            }
        }

        return $keys;
    }

    // -----------------------------------------------------------------------
    // Home content (HomeContentService)
    // -----------------------------------------------------------------------

    public static function homeCards(string $locale): string
    {
        return "content:home:cards:{$locale}";
    }

    public static function homeChips(string $locale): string
    {
        return "content:home:chips:{$locale}";
    }

    /**
     * @return array<int, string>
     */
    public static function allHomeCardKeys(): array
    {
        return array_map(static fn (string $locale): string => self::homeCards($locale), self::flushableLocales());
    }

    /**
     * @return array<int, string>
     */
    public static function allHomeChipKeys(): array
    {
        return array_map(static fn (string $locale): string => self::homeChips($locale), self::flushableLocales());
    }

    // -----------------------------------------------------------------------
    // Static pages (StaticPageService)
    // -----------------------------------------------------------------------

    public static function staticPage(string $slug, string $locale): string
    {
        return "content:page:{$slug}:{$locale}";
    }

    /**
     * All locale variants of one page's cache key.
     *
     * @return array<int, string>
     */
    public static function staticPageKeys(string $slug): array
    {
        return array_map(static fn (string $locale): string => self::staticPage($slug, $locale), self::flushableLocales());
    }

    // -----------------------------------------------------------------------
    // FAQs (FaqService)
    // -----------------------------------------------------------------------

    public static function faqs(string $locale): string
    {
        return "content:faqs:{$locale}";
    }

    /**
     * @return array<int, string>
     */
    public static function allFaqKeys(): array
    {
        return array_map(static fn (string $locale): string => self::faqs($locale), self::flushableLocales());
    }

    // -----------------------------------------------------------------------
    // Locale helpers
    // -----------------------------------------------------------------------

    /**
     * Locales whose per-locale keys must be invalidated on writes.
     *
     * @return array<int, string>
     */
    public static function flushableLocales(): array
    {
        return array_values(array_unique(array_merge(self::SUPPORTED_LOCALES, Language::getActiveCodes())));
    }
}
