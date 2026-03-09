<?php

declare(strict_types=1);

namespace App\Domain\Sahaba\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * SahabiTranslation Model
 *
 * Stores per-locale translations for Sahabi (Companion of the Prophet).
 *
 * @property int $id
 * @property int $sahabi_id
 * @property string $locale
 * @property string|null $name
 * @property string|null $full_name
 * @property string|null $kunya
 * @property string|null $laqab
 * @property string|null $biography
 * @property string|null $virtues
 * @property string|null $famous_narrations
 * @property bool $is_ready
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 *
 * @property-read Sahabi $sahabi
 */
class SahabiTranslation extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'sahabi_translations';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'sahabi_id',
        'locale',
        'name',
        'full_name',
        'kunya',
        'laqab',
        'biography',
        'virtues',
        'famous_narrations',
        'is_ready',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'sahabi_id' => 'integer',
        'is_ready' => 'boolean',
    ];

    /**
     * Get the parent sahabi (companion).
     */
    public function sahabi(): BelongsTo
    {
        return $this->belongsTo(Sahabi::class, 'sahabi_id');
    }

    /**
     * Scope: Filter by locale.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $locale
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByLocale($query, string $locale)
    {
        return $query->where('locale', $locale);
    }

    /**
     * Scope: Only ready translations.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeReady($query)
    {
        return $query->where('is_ready', true);
    }

    /**
     * Scope: Search name field.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $term
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSearchName($query, string $term)
    {
        return $query->where('name', 'LIKE', "%{$term}%");
    }

    /**
     * Scope: Search full name field.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $term
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSearchFullName($query, string $term)
    {
        return $query->where('full_name', 'LIKE', "%{$term}%");
    }

    /**
     * Scope: Search across name-related fields.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $term
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSearchAllNames($query, string $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('name', 'LIKE', "%{$term}%")
                ->orWhere('full_name', 'LIKE', "%{$term}%")
                ->orWhere('kunya', 'LIKE', "%{$term}%")
                ->orWhere('laqab', 'LIKE', "%{$term}%");
        });
    }

    /**
     * Check if this translation is RTL.
     */
    public function isRtl(): bool
    {
        return in_array($this->locale, ['ar', 'ur']);
    }
}
