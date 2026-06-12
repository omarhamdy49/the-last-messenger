<?php

declare(strict_types=1);

namespace App\Domain\Media\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * MediaCategory Model
 *
 * One row per ProgramCategory enum value. `is_featured` controls whether the
 * category row and its filter pill load on the public programs list (/media).
 * `name`/`description` are optional per-locale display overrides; labels fall
 * back to the media.public.category_{key} lang entries.
 *
 * @property int $id
 * @property string $key
 * @property array|null $name
 * @property array|null $description
 * @property string|null $icon
 * @property string|null $color
 * @property bool $is_featured
 * @property int $display_order
 */
class MediaCategory extends Model
{
    protected $table = 'media_categories';

    protected $fillable = [
        'key',
        'name',
        'description',
        'icon',
        'color',
        'is_featured',
        'display_order',
    ];

    protected $casts = [
        'name' => 'array',
        'description' => 'array',
        'is_featured' => 'boolean',
        'display_order' => 'integer',
    ];

    /** Display name for a locale: stored override → ar override → lang entry. */
    public function localizedName(?string $locale = null): string
    {
        $locale = $locale ?? app()->getLocale();

        return $this->name[$locale]
            ?? $this->name['ar']
            ?? __('media.public.category_'.$this->key);
    }

    public function localizedDescription(?string $locale = null): ?string
    {
        $locale = $locale ?? app()->getLocale();

        return $this->description[$locale] ?? $this->description['ar'] ?? null;
    }

    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('display_order');
    }
}
