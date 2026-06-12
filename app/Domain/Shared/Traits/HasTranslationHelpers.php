<?php

declare(strict_types=1);

namespace App\Domain\Shared\Traits;

use App\Domain\Shared\Models\Language;
use App\Domain\Shared\Services\TranslationHelperService;

/**
 * Has Translation Helpers Trait
 *
 * Provides helper methods for models with translatable attributes
 */
trait HasTranslationHelpers
{
    /**
     * Get translation with enhanced fallback logic
     *
     * @param  string  $key  Attribute name
     * @param  string|null  $locale  Language code
     * @param  bool  $useFallbackLocale  Whether to use fallback locale
     */
    public function getTranslationWithFallback(string $key, ?string $locale = null, bool $useFallbackLocale = true): mixed
    {
        $locale = $locale ?? app()->getLocale();

        // Try to get translation from Spatie package
        if (method_exists($this, 'getTranslation')) {
            $value = parent::getTranslation($key, $locale, $useFallbackLocale);

            if (! empty($value)) {
                return $value;
            }
        }

        // Fallback to default language
        $defaultLang = Language::getDefaultCode();
        if ($locale !== $defaultLang && method_exists($this, 'getTranslation')) {
            $value = parent::getTranslation($key, $defaultLang, false);

            if (! empty($value)) {
                return $value;
            }
        }

        // Return first available translation
        $translations = $this->getTranslations($key);
        foreach ($translations as $translation) {
            if (! empty($translation)) {
                return $translation;
            }
        }

        return null;
    }

    /**
     * Get translation for current app locale
     *
     * @param  string  $key  Attribute name
     */
    public function trans(string $key): mixed
    {
        return $this->getTranslationWithFallback($key, app()->getLocale());
    }

    /**
     * Get all translations for a key
     *
     * @param  string  $key  Attribute name
     */
    public function getAllTranslations(string $key): array
    {
        if (method_exists($this, 'getTranslations')) {
            return $this->getTranslations($key);
        }

        return [];
    }

    /**
     * Check if translation exists for locale
     *
     * @param  string  $key  Attribute name
     * @param  string  $locale  Language code
     */
    public function hasTranslation(string $key, string $locale): bool
    {
        if (! method_exists($this, 'getTranslation')) {
            return false;
        }

        $value = $this->getTranslation($key, $locale, false);

        return ! empty($value);
    }

    /**
     * Check if all translations are complete
     *
     * @param  string  $key  Attribute name
     */
    public function hasCompleteTranslations(string $key): bool
    {
        $translations = $this->getAllTranslations($key);
        $activeCodes = Language::getActiveCodes();

        foreach ($activeCodes as $code) {
            if (! isset($translations[$code]) || empty(trim((string) $translations[$code]))) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get missing translation codes
     *
     * @param  string  $key  Attribute name
     */
    public function getMissingTranslationCodes(string $key): array
    {
        $translations = $this->getAllTranslations($key);
        $activeCodes = Language::getActiveCodes();
        $missing = [];

        foreach ($activeCodes as $code) {
            if (! isset($translations[$code]) || empty(trim((string) $translations[$code]))) {
                $missing[] = $code;
            }
        }

        return $missing;
    }

    /**
     * Set translation for specific locale
     *
     * @param  string  $key  Attribute name
     * @param  string  $locale  Language code
     * @param  mixed  $value  Translation value
     */
    public function setTranslationForLocale(string $key, string $locale, mixed $value): self
    {
        if (method_exists($this, 'setTranslation')) {
            $this->setTranslation($key, $locale, $value);
        }

        return $this;
    }

    /**
     * Fill missing translations with default language value
     *
     * @param  string  $key  Attribute name
     */
    public function fillMissingTranslations(string $key): self
    {
        $translations = $this->getAllTranslations($key);
        $defaultLang = Language::getDefaultCode();
        $defaultValue = $translations[$defaultLang] ?? '';

        $activeCodes = Language::getActiveCodes();

        foreach ($activeCodes as $code) {
            if (! isset($translations[$code]) || empty($translations[$code])) {
                $this->setTranslationForLocale($key, $code, $defaultValue);
            }
        }

        return $this;
    }

    /**
     * Get translation helper service
     */
    protected function getTranslationHelper(): TranslationHelperService
    {
        return app(TranslationHelperService::class);
    }

    /**
     * Sanitize translatable attributes before saving
     */
    protected function sanitizeTranslatableAttributes(): void
    {
        if (! property_exists($this, 'translatable')) {
            return;
        }

        $helper = $this->getTranslationHelper();

        foreach ($this->translatable as $attribute) {
            $translations = $this->getAllTranslations($attribute);

            if (! empty($translations)) {
                $sanitized = $helper->sanitizeTranslations($translations);

                foreach ($sanitized as $locale => $value) {
                    $this->setTranslationForLocale($attribute, $locale, $value);
                }
            }
        }
    }
}
