<?php

declare(strict_types=1);

namespace App\Domain\Sahaba\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * SahabiTimelineItemTranslation Model
 *
 * Per-locale title/description for a companion timeline entry.
 *
 * @property int $id
 * @property int $sahabi_timeline_item_id
 * @property string $locale
 * @property string|null $title
 * @property string|null $description
 * @property bool $is_ready
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property-read SahabiTimelineItem $timelineItem
 */
class SahabiTimelineItemTranslation extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'sahabi_timeline_item_translations';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'sahabi_timeline_item_id',
        'locale',
        'title',
        'description',
        'is_ready',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'sahabi_timeline_item_id' => 'integer',
        'is_ready' => 'boolean',
    ];

    /**
     * Get the parent timeline item.
     */
    public function timelineItem(): BelongsTo
    {
        return $this->belongsTo(SahabiTimelineItem::class, 'sahabi_timeline_item_id');
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
