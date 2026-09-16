<?php

declare(strict_types=1);

namespace App\Domain\Shared\Services;

use App\Domain\Shared\Models\Language;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Language Service
 *
 * Manages language-related operations
 */
class LanguageService
{
    /**
     * Get all languages
     */
    public function getAllLanguages(): Collection
    {
        return Language::getAllCached();
    }

    /**
     * Get active languages
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
     * Get language by code
     */
    public function getLanguageByCode(string $code): ?Language
    {
        return Language::getAllCached()->firstWhere('code', $code);
    }

    /**
     * Check if language code is valid
     */
    public function isValidLanguageCode(string $code): bool
    {
        return Language::isValidCode($code);
    }

    /**
     * Get language codes
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
     */
    public function getRtlLanguages(): Collection
    {
        return Language::getActiveCached()->where('direction', 'rtl');
    }

    /**
     * Get LTR languages
     */
    public function getLtrLanguages(): Collection
    {
        return Language::getActiveCached()->where('direction', 'ltr');
    }

    /**
     * Check if language is RTL
     */
    public function isRtl(string $code): bool
    {
        $language = $this->getLanguageByCode($code);

        return $language ? $language->direction === 'rtl' : false;
    }

    /**
     * Create or update language
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

    /** Refresh runtime configuration from the same active records used by selectors. */
    public function applyRuntimeConfiguration(): void
    {
        $languages = $this->getActiveLanguages();
        $codes = $languages->pluck('code')->all();
        $default = $this->getDefaultLanguage()?->code ?? 'ar';
        if ($codes === []) {
            $codes = config('languages.default_enabled');
        }
        $metadata = $languages->mapWithKeys(fn (Language $language): array => [
            $language->code => ['name' => $language->native_name, 'dir' => $language->direction, 'flag' => $language->flag_icon],
        ])->all();
        if ($metadata === []) {
            foreach ($codes as $code) {
                $language = config('languages.available.'.$code);
                $metadata[$code] = ['name' => $language['native_name'], 'dir' => $language['direction'], 'flag' => $language['flag']];
            }
        }
        config([
            'translatable.locales' => $codes,
            'translatable.fallback_locale' => $default,
            'app.fallback_locale' => $default,
            'app.supported_locales' => $metadata,
        ]);
        app()->instance('available_locales', $codes);
    }

    /** Settings and selectors share the persisted language records. */
    public function settings(): array
    {
        return $this->getAllLanguages()->mapWithKeys(fn (Language $language): array => [
            $language->code => [
                'code' => $language->code,
                'name' => config('languages.available.'.$language->code.'.name', $language->native_name),
                'native_name' => $language->native_name,
                'direction' => $language->direction,
                'flag' => $language->flag_icon,
                'active' => $language->is_active,
                'order' => $language->sort_order,
            ],
        ])->all();
    }

    public function updateSettings(string $code, array $attributes): Language
    {
        return DB::transaction(function () use ($code, $attributes): Language {
            $language = Language::query()->where('code', $code)->lockForUpdate()->firstOrFail();
            if (array_key_exists('active', $attributes)) {
                $this->assertCanDeactivate($language, (bool) $attributes['active']);
                $language->is_active = (bool) $attributes['active'];
            }
            if (isset($attributes['order'])) {
                $language->sort_order = $attributes['order'];
            }
            $language->save();

            return $language;
        });
    }

    public function toggleLanguageStatus(string $code): bool
    {
        DB::transaction(function () use ($code): void {
            $language = Language::query()->where('code', $code)->lockForUpdate()->firstOrFail();
            $this->assertCanDeactivate($language, ! $language->is_active);
            $language->update(['is_active' => ! $language->is_active]);
        });

        return true;
    }

    public function setDefaultLanguage(string $code): bool
    {
        DB::transaction(function () use ($code): void {
            $languages = Language::query()->orderBy('id')->lockForUpdate()->get();
            $language = $languages->firstWhere('code', $code);
            abort_unless($language, 404);
            if (! $language->is_active) {
                throw ValidationException::withMessages(['code' => 'لا يمكن تعيين لغة غير نشطة كلغة افتراضية']);
            }
            if ($language->is_default) {
                return;
            }
            Language::query()->where('is_default', true)->update(['is_default' => false]);
            $language->update(['is_default' => true]);
        });
        Language::clearCache();

        return true;
    }

    public function reorder(array $codes): void
    {
        $allCodes = $this->getLanguageCodes(false);
        if (count($codes) !== count($allCodes) || array_diff($allCodes, $codes) !== []) {
            throw ValidationException::withMessages(['languages' => 'يجب تضمين جميع اللغات دون تكرار']);
        }
        DB::transaction(function () use ($codes): void {
            foreach ($codes as $index => $code) {
                Language::query()->where('code', $code)->update(['sort_order' => $index + 1]);
            }
        });
        Language::clearCache();
    }

    private function assertCanDeactivate(Language $language, bool $active): void
    {
        if (! $active && ($language->is_default || $language->code === 'ar')) {
            throw ValidationException::withMessages(['active' => 'لا يمكن تعطيل اللغة الافتراضية أو العربية المطلوبة للمحتوى']);
        }
    }

    /** Create missing records without overwriting an administrator's choices. */
    public function seedDefaultLanguages(): void
    {
        foreach (config('languages.available') as $code => $metadata) {
            Language::firstOrCreate(['code' => $code], [
                'native_name' => $metadata['native_name'],
                'direction' => $metadata['direction'],
                'flag_icon' => $metadata['flag'],
                'is_active' => in_array($code, config('languages.default_enabled'), true),
                'is_default' => $code === 'ar',
                'sort_order' => array_search($code, array_keys(config('languages.available')), true) + 1,
            ]);
        }
    }
}
