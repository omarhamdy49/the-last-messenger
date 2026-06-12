<?php

declare(strict_types=1);

namespace App\Domain\Content\Repositories;

use App\Domain\Content\Models\SiteSetting;
use Illuminate\Database\Eloquent\Collection;

interface SiteSettingRepositoryInterface
{
    /**
     * Get all settings with their translations (admin + cache priming).
     *
     * @return Collection<int, SiteSetting>
     */
    public function allWithTranslations(): Collection;

    /**
     * Get the settings of one group with translations (admin forms).
     *
     * @return Collection<int, SiteSetting>
     */
    public function getByGroup(string $group): Collection;

    /**
     * Create or update a setting and (for translatable settings) its
     * per-locale values, atomically.
     *
     * @param  array<string, mixed>  $attributes  Setting columns (group/type/value/...)
     * @param  array<string, string|null>|null  $translations  Locale-keyed values
     */
    public function upsert(string $key, array $attributes, ?array $translations = null): SiteSetting;

    /**
     * Distinct setting group names, ordered by each group's first
     * sort_order (admin tab order).
     *
     * @return array<int, string>
     */
    public function groups(): array;
}
