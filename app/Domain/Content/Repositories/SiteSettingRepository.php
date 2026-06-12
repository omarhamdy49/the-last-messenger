<?php

declare(strict_types=1);

namespace App\Domain\Content\Repositories;

use App\Domain\Content\Models\SiteSetting;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Eloquent implementation of the site settings repository.
 * All DB access for site settings lives here.
 */
class SiteSettingRepository implements SiteSettingRepositoryInterface
{
    /**
     * Columns read by the settings cache and admin forms.
     *
     * @var array<int, string>
     */
    private const SETTING_COLUMNS = ['id', 'key', 'group', 'type', 'value', 'is_translatable', 'sort_order'];

    private const TRANSLATION_COLUMNS = 'translations:id,site_setting_id,locale,value,is_ready';

    public function allWithTranslations(): Collection
    {
        return SiteSetting::query()
            ->select(self::SETTING_COLUMNS)
            ->with([self::TRANSLATION_COLUMNS])
            ->orderBy('sort_order')
            ->get();
    }

    public function getByGroup(string $group): Collection
    {
        return SiteSetting::query()
            ->select(self::SETTING_COLUMNS)
            ->with([self::TRANSLATION_COLUMNS])
            ->byGroup($group)
            ->orderBy('sort_order')
            ->get();
    }

    public function upsert(string $key, array $attributes, ?array $translations = null): SiteSetting
    {
        return DB::transaction(function () use ($key, $attributes, $translations): SiteSetting {
            /** @var SiteSetting $setting */
            $setting = SiteSetting::query()->updateOrCreate(['key' => $key], $attributes);

            if ($translations !== null) {
                foreach ($translations as $locale => $value) {
                    $setting->translations()->updateOrCreate(
                        ['site_setting_id' => $setting->id, 'locale' => $locale],
                        ['value' => $value, 'is_ready' => ! empty(trim((string) ($value ?? '')))]
                    );
                }
            }

            return $setting->load('translations');
        });
    }

    public function groups(): array
    {
        return SiteSetting::query()
            ->selectRaw('`group`, MIN(sort_order) AS first_sort')
            ->groupBy('group')
            ->orderBy('first_sort')
            ->pluck('group')
            ->all();
    }
}
