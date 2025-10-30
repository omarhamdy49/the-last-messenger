<?php

declare(strict_types=1);

namespace App\Domain\Shared\Services;

use App\Domain\Shared\Models\Language;
use Illuminate\Support\Collection;

/**
 * Language Service
 *
 * Manages language-related operations
 */
class LanguageService
{
    /**
     * Get all languages
     *
     * @return Collection
     */
    public function getAllLanguages(): Collection
    {
        return Language::getAllCached();
    }

    /**
     * Get active languages
     *
     * @return Collection
     */
    public function getActiveLanguages(): Collection
    {
        return Language::getActiveCached();
    }

    /**
     * Get default language
     *
     * @return Language|null
     */
    public function getDefaultLanguage(): ?Language
    {
        return Language::getDefaultCached();
    }

    /**
     * Get language by code
     *
     * @param string $code
     * @return Language|null
     */
    public function getLanguageByCode(string $code): ?Language
    {
        return Language::getAllCached()->firstWhere('code', $code);
    }

    /**
     * Check if language code is valid
     *
     * @param string $code
     * @return bool
     */
    public function isValidLanguageCode(string $code): bool
    {
        return Language::isValidCode($code);
    }

    /**
     * Get language codes
     *
     * @param bool $activeOnly
     * @return array
     */
    public function getLanguageCodes(bool $activeOnly = true): array
    {
        if ($activeOnly) {
            return Language::getActiveCodes();
        }

        return Language::getAllCached()->pluck('code')->toArray();
    }

    /**
     * Get RTL languages
     *
     * @return Collection
     */
    public function getRtlLanguages(): Collection
    {
        return Language::getActiveCached()->where('direction', 'rtl');
    }

    /**
     * Get LTR languages
     *
     * @return Collection
     */
    public function getLtrLanguages(): Collection
    {
        return Language::getActiveCached()->where('direction', 'ltr');
    }

    /**
     * Check if language is RTL
     *
     * @param string $code
     * @return bool
     */
    public function isRtl(string $code): bool
    {
        $language = $this->getLanguageByCode($code);
        return $language ? $language->direction === 'rtl' : false;
    }

    /**
     * Create or update language
     *
     * @param array $data
     * @return Language
     */
    public function createOrUpdateLanguage(array $data): Language
    {
        $language = Language::updateOrCreate(
            ['code' => $data['code']],
            $data
        );

        Language::clearCache();

        return $language;
    }

    /**
     * Toggle language status
     *
     * @param string $code
     * @return bool
     */
    public function toggleLanguageStatus(string $code): bool
    {
        $language = $this->getLanguageByCode($code);

        if (! $language) {
            return false;
        }

        $language->is_active = ! $language->is_active;
        $language->save();

        Language::clearCache();

        return true;
    }

    /**
     * Set default language
     *
     * @param string $code
     * @return bool
     */
    public function setDefaultLanguage(string $code): bool
    {
        $language = $this->getLanguageByCode($code);

        if (! $language) {
            return false;
        }

        // Remove default from all languages
        Language::where('is_default', true)->update(['is_default' => false]);

        // Set new default
        $language->is_default = true;
        $language->save();

        Language::clearCache();

        return true;
    }

    /**
     * Seed default languages
     *
     * @return void
     */
    public function seedDefaultLanguages(): void
    {
        $languages = [
            [
                'code' => 'ar',
                'name' => 'Arabic',
                'native_name' => 'العربية',
                'direction' => 'rtl',
                'is_default' => true,
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'code' => 'en',
                'name' => 'English',
                'native_name' => 'English',
                'direction' => 'ltr',
                'is_default' => false,
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'code' => 'fr',
                'name' => 'French',
                'native_name' => 'Français',
                'direction' => 'ltr',
                'is_default' => false,
                'is_active' => true,
                'sort_order' => 3,
            ],
            [
                'code' => 'tr',
                'name' => 'Turkish',
                'native_name' => 'Türkçe',
                'direction' => 'ltr',
                'is_default' => false,
                'is_active' => true,
                'sort_order' => 4,
            ],
            [
                'code' => 'ur',
                'name' => 'Urdu',
                'native_name' => 'اردو',
                'direction' => 'rtl',
                'is_default' => false,
                'is_active' => true,
                'sort_order' => 5,
            ],
        ];

        foreach ($languages as $languageData) {
            $this->createOrUpdateLanguage($languageData);
        }
    }
}
