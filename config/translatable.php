<?php

declare(strict_types=1);

return [
    /*
     * The locales used by the application.
     * These are dynamically loaded from cache for better performance.
     * Fallback to static array if cache service is not available.
     */
    'locales' => function () {
        try {
            // Try to get active language codes from cache service
            $cacheService = app(\App\Domain\Shared\Services\LanguageCacheService::class);

            return $cacheService->getActiveLanguageCodes();
        } catch (\Throwable $e) {
            // Fallback to default languages if cache service fails
            return ['ar', 'en', 'fr', 'tr', 'ur'];
        }
    },

    /*
     * If a translation is not found for a given locale, fallback locale will be used.
     * Arabic is set as default fallback as it's the primary language.
     * Set to false to disable fallback.
     */
    'fallback_locale' => 'ar',

    /*
     * If you want to use fallback for all translatable attributes.
     * You can override this per attribute with $translatable property.
     *
     * Enabled for better user experience - shows Arabic content if translation missing.
     */
    'use_fallback' => true,

    /*
     * Suffix for the locale attribute.
     * E.g., if set to '_locale', then the attribute will be 'title_locale'.
     */
    'locale_separator' => ':',

    /*
     * If you want to store translations in the database as JSON.
     * This is the recommended way for Laravel and what we use.
     *
     * Translations are stored as: {"ar": "...", "en": "...", ...}
     */
    'use_property_fallback' => true,

    /*
     * If true, empty translations will fallback to any available locale.
     *
     * Set to true for better UX - shows content in any language rather than nothing.
     */
    'fallback_any_locale' => true,

    /*
     * If true, translatable attributes will be cast to array automatically.
     *
     * Enabled to ensure translations are always available as arrays in API responses.
     */
    'to_array_always_loads_translations' => true,

    /*
     * Performance optimization: Cache translated attributes
     *
     * When enabled, translation lookups are cached per model instance
     * to avoid repeated JSON parsing of the same field.
     */
    'cache_translations' => env('CACHE_TRANSLATIONS', true),

    /*
     * Performance optimization: Eager load translations
     *
     * When disabled, translations are loaded on-demand (lazy loading).
     * Our translations are in the same table, so this should be false.
     */
    'eager_load_translations' => false,
];
