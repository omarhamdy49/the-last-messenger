<?php

declare(strict_types=1);

namespace App\Domain\Content\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * QuickFilterChipTranslation Model
 *
 * @property int $id
 * @property int $quick_filter_chip_id
 * @property string $locale
 * @property string|null $label
 * @property bool $is_ready
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property-read QuickFilterChip $chip
 */
class QuickFilterChipTranslation extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'quick_filter_chip_translations';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'quick_filter_chip_id',
        'locale',
        'label',
        'is_ready',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'quick_filter_chip_id' => 'integer',
        'is_ready' => 'boolean',
    ];

    /**
     * Get the parent chip.
     */
    public function chip(): BelongsTo
    {
        return $this->belongsTo(QuickFilterChip::class, 'quick_filter_chip_id');
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
