<?php

declare(strict_types=1);

namespace App\Domain\Content\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * SiteSettingTranslation Model
 *
 * Per-locale value for translatable site settings.
 *
 * @property int $id
 * @property int $site_setting_id
 * @property string $locale
 * @property string|null $value
 * @property bool $is_ready
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property-read SiteSetting $siteSetting
 */
class SiteSettingTranslation extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'site_setting_translations';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'site_setting_id',
        'locale',
        'value',
        'is_ready',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'site_setting_id' => 'integer',
        'is_ready' => 'boolean',
    ];

    /**
     * Get the parent setting.
     */
    public function siteSetting(): BelongsTo
    {
        return $this->belongsTo(SiteSetting::class, 'site_setting_id');
    }

    /**
     * Scope: Filter by locale.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByLocale($query, string $locale)
    {
        return $query->where('locale', $locale);
    }
}
