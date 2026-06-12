<?php

declare(strict_types=1);

namespace App\Domain\Shared\Services;

use App\Domain\Shared\Models\Language;
use Illuminate\Support\Collection;

/**
 * Translation Helper Service
 *
 * Provides utility methods for handling multi-language translations
 */
class TranslationHelperService
{
    /**
     * Get all active languages
     */
    public function getActiveLanguages(): Collection
    {
        return Language::getActiveCached();
    }

    /**
     * Get default language
     */
    public function getDefaultLanguage(): ?Language
    {
        return Language::getDefaultCached();
    }

    /**
     * Get default language code
     */
    public function getDefaultLanguageCode(): string
    {
        return Language::getDefaultCode();
    }

    /**
     * Get active language codes
     */
    public function getActiveLanguageCodes(): array
    {
        return Language::getActiveCodes();
    }

    /**
     * Validate that translation data has required languages
     *
     * @param  array  $data  Translation data
     * @param  string  $field  Field name to validate
     * @param  bool  $requireDefault  Whether default language is required
     * @return array Array with 'valid' bool and 'errors' array
     */
    public function validateTranslation(array $data, string $field, bool $requireDefault = true): array
    {
        $errors = [];
        $defaultLang = $this->getDefaultLanguageCode();

        // Check if field exists
        if (! isset($data[$field])) {
            return [
                'valid' => false,
                'errors' => ["The {$field} field is required."],
            ];
        }

        $translations = $data[$field];

        // Check if it's an array
        if (! is_array($translations)) {
            return [
                'valid' => false,
                'errors' => ["The {$field} must be an array of translations."],
            ];
        }

        // Check default language
        if ($requireDefault && (! isset($translations[$defaultLang]) || empty(trim((string) $translations[$defaultLang])))) {
            $errors[] = "The {$field} must include {$defaultLang} translation.";
        }

        // Check for invalid language codes
        $validCodes = $this->getActiveLanguageCodes();
        $invalidCodes = array_diff(array_keys($translations), $validCodes);

        if (! empty($invalidCodes)) {
            $errors[] = "The {$field} contains invalid language codes: ".implode(', ', $invalidCodes);
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }

    /**
     * Fill missing translations with default language value or empty string
     *
     * @param  array  $data  Translation data
     * @param  bool  $useDefaultValue  Whether to copy default language value
     * @return array Filled translation data
     */
    public function fillMissingTranslations(array $data, bool $useDefaultValue = false): array
    {
        $activeCodes = $this->getActiveLanguageCodes();
        $defaultLang = $this->getDefaultLanguageCode();
        $defaultValue = $useDefaultValue && isset($data[$defaultLang]) ? $data[$defaultLang] : '';

        foreach ($activeCodes as $code) {
            if (! isset($data[$code]) || $data[$code] === null) {
                $data[$code] = $defaultValue;
            }
        }

        return $data;
    }

    /**
     * Sanitize translations (trim, remove empty values)
     *
     * @param  array  $data  Translation data
     * @param  bool  $removeEmpty  Whether to remove empty translations
     * @return array Sanitized translation data
     */
    public function sanitizeTranslations(array $data, bool $removeEmpty = false): array
    {
        $sanitized = [];

        foreach ($data as $code => $value) {
            if (is_string($value)) {
                $trimmed = trim($value);

                if ($removeEmpty && empty($trimmed)) {
                    continue;
                }

                $sanitized[$code] = $trimmed;
            } else {
                $sanitized[$code] = $value;
            }
        }

        return $sanitized;
    }

    /**
     * Get translation for specific language with fallback
     *
     * @param  array  $translations  Translation array
     * @param  string|null  $locale  Desired language code (null for app locale)
     * @param  string|null  $fallback  Fallback language code (null for default)
     */
    public function getTranslation(array $translations, ?string $locale = null, ?string $fallback = null): string
    {
        $locale = $locale ?? app()->getLocale();
        $fallback = $fallback ?? $this->getDefaultLanguageCode();

        // Try requested locale
        if (isset($translations[$locale]) && ! empty($translations[$locale])) {
            return $translations[$locale];
        }

        // Try fallback
        if (isset($translations[$fallback]) && ! empty($translations[$fallback])) {
            return $translations[$fallback];
        }

        // Return first available translation
        foreach ($translations as $value) {
            if (! empty($value)) {
                return $value;
            }
        }

        return '';
    }

    /**
     * Prepare translatable fields for storage
     *
     * @param  array  $data  Input data
     * @param  array  $translatableFields  List of translatable field names
     * @return array Prepared data
     */
    public function prepareForStorage(array $data, array $translatableFields): array
    {
        foreach ($translatableFields as $field) {
            if (isset($data[$field])) {
                $data[$field] = $this->sanitizeTranslations($data[$field]);
                $data[$field] = $this->fillMissingTranslations($data[$field], false);
            }
        }

        return $data;
    }

    /**
     * Create empty translation array with all language codes
     *
     * @param  string  $defaultValue  Default value for all languages
     */
    public function createEmptyTranslationArray(string $defaultValue = ''): array
    {
        $activeCodes = $this->getActiveLanguageCodes();

        return array_fill_keys($activeCodes, $defaultValue);
    }

    /**
     * Check if translation is complete (all languages have values)
     *
     * @param  array  $translations  Translation array
     */
    public function isTranslationComplete(array $translations): bool
    {
        $activeCodes = $this->getActiveLanguageCodes();

        foreach ($activeCodes as $code) {
            if (! isset($translations[$code]) || empty(trim((string) $translations[$code]))) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get missing translations
     *
     * @param  array  $translations  Translation array
     * @return array Array of missing language codes
     */
    public function getMissingTranslations(array $translations): array
    {
        $activeCodes = $this->getActiveLanguageCodes();
        $missing = [];

        foreach ($activeCodes as $code) {
            if (! isset($translations[$code]) || empty(trim((string) $translations[$code]))) {
                $missing[] = $code;
            }
        }

        return $missing;
    }
}
