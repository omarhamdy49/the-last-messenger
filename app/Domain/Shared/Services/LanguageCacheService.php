<?php

declare(strict_types=1);

namespace App\Domain\Shared\Services;

use App\Domain\Shared\Models\Language;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Language Cache Service
 *
 * Provides optimized caching layer for language data to improve performance
 * across multi-language operations.
 */
class LanguageCacheService
{
    /**
     * Base cache key for language data
     */
    private const CACHE_KEY = 'app.languages';

    /**
     * Cache TTL in seconds (1 hour)
     */
    private const CACHE_TTL = 3600;

    /**
     * Cache tags for organized invalidation (only for Redis/Memcached)
     */
    private const CACHE_TAGS = ['languages'];

    /**
     * Check if cache driver supports tagging
     */
    protected function supportsTags(): bool
    {
        return method_exists(Cache::getStore(), 'tags');
    }

    /**
     * Get cache store (with or without tags)
     *
     * @return \Illuminate\Contracts\Cache\Repository
     */
    protected function cache(): mixed
    {
        return $this->supportsTags()
            ? Cache::tags(self::CACHE_TAGS)
            : Cache::store();
    }

    /**
     * Get all active languages ordered by sort order
     *
     * @return Collection<Language>
     */
    public function getActiveLanguages(): Collection
    {
        return $this->cache()->remember(
            self::CACHE_KEY.'.active',
            self::CACHE_TTL,
            fn () => Language::where('is_active', true)
                ->orderBy('sort_order')
                ->get()
        );
    }

    /**
     * Get the default language
     */
    public function getDefaultLanguage(): ?Language
    {
        return $this->cache()->remember(
            self::CACHE_KEY.'.default',
            self::CACHE_TTL,
            fn () => Language::where('is_default', true)->first()
        );
    }

    /**
     * Get language by code
     *
     * @param  string  $code  Language code (e.g., 'ar', 'en')
     */
    public function getLanguageByCode(string $code): ?Language
    {
        return $this->cache()->remember(
            self::CACHE_KEY.'.code.'.$code,
            self::CACHE_TTL,
            fn () => Language::where('code', $code)->first()
        );
    }

    /**
     * Get all active language codes as array
     *
     * @return array<string>
     */
    public function getActiveLanguageCodes(): array
    {
        return $this->cache()->remember(
            self::CACHE_KEY.'.codes',
            self::CACHE_TTL,
            fn () => Language::where('is_active', true)
                ->orderBy('sort_order')
                ->pluck('code')
                ->toArray()
        );
    }

    /**
     * Get default language code
     */
    public function getDefaultLanguageCode(): string
    {
        $defaultLanguage = $this->getDefaultLanguage();

        return $defaultLanguage?->code ?? 'ar';
    }

    /**
     * Get language direction (rtl/ltr) by code
     *
     * @param  string  $code  Language code
     * @return string 'rtl' or 'ltr'
     */
    public function getLanguageDirection(string $code): string
    {
        $language = $this->getLanguageByCode($code);

        return $language?->direction ?? 'ltr';
    }

    /**
     * Check if language code is RTL
     *
     * @param  string  $code  Language code
     */
    public function isRtl(string $code): bool
    {
        return $this->getLanguageDirection($code) === 'rtl';
    }

    /**
     * Get all languages with their metadata
     *
     * @return Collection<Language>
     */
    public function getAllLanguages(): Collection
    {
        return $this->cache()->remember(
            self::CACHE_KEY.'.all',
            self::CACHE_TTL,
            fn () => Language::orderBy('sort_order')->get()
        );
    }

    /**
     * Warm up the cache with all language data
     */
    public function warmCache(): void
    {
        $this->getActiveLanguages();
        $this->getDefaultLanguage();
        $this->getActiveLanguageCodes();
        $this->getAllLanguages();
    }

    /**
     * Clear all language-related cache
     */
    public function clearCache(): void
    {
        if ($this->supportsTags()) {
            Cache::tags(self::CACHE_TAGS)->flush();
        } else {
            // Manual cache key clearing for non-tagged stores
            $keys = [
                self::CACHE_KEY.'.active',
                self::CACHE_KEY.'.default',
                self::CACHE_KEY.'.codes',
                self::CACHE_KEY.'.all',
            ];

            foreach ($keys as $key) {
                Cache::forget($key);
            }

            // Clear individual language code caches (common codes)
            foreach (['ar', 'en', 'fr', 'tr', 'ur'] as $code) {
                Cache::forget(self::CACHE_KEY.'.code.'.$code);
            }
        }
    }

    /**
     * Refresh cache after language changes
     */
    public function refreshCache(): void
    {
        $this->clearCache();
        $this->warmCache();
    }

    /**
     * Get cache statistics
     *
     * @return array<string, mixed>
     */
    public function getCacheStats(): array
    {
        $keys = [
            'active' => self::CACHE_KEY.'.active',
            'default' => self::CACHE_KEY.'.default',
            'codes' => self::CACHE_KEY.'.codes',
            'all' => self::CACHE_KEY.'.all',
        ];

        $stats = [];
        foreach ($keys as $name => $key) {
            $stats[$name] = Cache::has($key);
        }

        return $stats;
    }
}
