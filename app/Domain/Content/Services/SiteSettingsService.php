<?php

declare(strict_types=1);

namespace App\Domain\Content\Services;

use App\Domain\Content\Enums\SiteSettingType;
use App\Domain\Content\Models\SiteSetting;
use App\Domain\Content\Repositories\SiteSettingRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Site Settings Service
 *
 * Public reads come from a single forever-cached settings map (settings are
 * stable data — one cache entry, no per-key queries). Every write flushes
 * the map. Admin group reads bypass the cache so forms always show fresh data.
 */
final class SiteSettingsService
{
    private const FALLBACK_LOCALE = 'ar';

    public function __construct(
        private readonly SiteSettingRepositoryInterface $repository,
    ) {}

    /**
     * Get a setting value for the public site.
     *
     * Translatable settings resolve the requested locale with Arabic
     * fallback; `bool` settings are cast; everything else returns the raw
     * stored value. Unknown keys return null.
     */
    public function get(string $key, ?string $locale = null): string|bool|null
    {
        $setting = $this->allCached()[$key] ?? null;

        if ($setting === null) {
            return null;
        }

        if ($setting['is_translatable']) {
            $locale = $locale ?? app()->getLocale();

            return $setting['translations'][$locale]
                ?? $setting['translations'][self::FALLBACK_LOCALE]
                ?? null;
        }

        if ($setting['type'] === SiteSettingType::BOOL->value) {
            return filter_var($setting['value'], FILTER_VALIDATE_BOOL);
        }

        return $setting['value'];
    }

    /**
     * Create or update a setting and flush the cache.
     *
     * Pass a locale-keyed array (['ar' => ..., 'en' => ...]) for translatable
     * settings, or a scalar for plain ones. `$attributes` may carry
     * group/type/sort_order when creating new settings.
     *
     * @param  array<string, string|null>|string|bool|null  $value
     * @param  array<string, mixed>  $attributes
     */
    public function set(string $key, array|string|bool|null $value, array $attributes = []): SiteSetting
    {
        $translations = null;

        if (is_array($value)) {
            $translations = $value;
            $attributes['is_translatable'] = true;
        } else {
            $attributes['value'] = is_bool($value) ? ($value ? '1' : '0') : $value;
            $attributes['is_translatable'] = $attributes['is_translatable'] ?? false;
        }

        $setting = $this->repository->upsert($key, $attributes, $translations);

        $this->flush();

        return $setting;
    }

    /**
     * Get one group's settings with translations for admin forms (uncached).
     *
     * @return Collection<int, SiteSetting>
     */
    public function group(string $group): Collection
    {
        return $this->repository->getByGroup($group);
    }

    /**
     * Invalidate the cached settings map.
     */
    public function flush(): void
    {
        Cache::forget(ContentCacheKeys::settingsAll());
    }

    /**
     * The forever-cached settings map, keyed by setting key.
     *
     * @return array<string, array{type: string, is_translatable: bool, value: string|null, translations: array<string, string|null>}>
     */
    private function allCached(): array
    {
        return Cache::rememberForever(ContentCacheKeys::settingsAll(), function (): array {
            return $this->repository->allWithTranslations()
                ->mapWithKeys(static fn (SiteSetting $setting): array => [
                    $setting->key => [
                        'type' => $setting->type->value,
                        'is_translatable' => $setting->is_translatable,
                        'value' => $setting->value,
                        'translations' => $setting->translations->pluck('value', 'locale')->all(),
                    ],
                ])
                ->all();
        });
    }

    /**
     * Distinct setting group names for the admin settings tabs.
     *
     * @return array<int, string>
     */
    public function groups(): array
    {
        return $this->repository->groups();
    }
}
