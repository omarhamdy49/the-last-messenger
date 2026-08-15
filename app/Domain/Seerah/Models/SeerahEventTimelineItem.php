<?php

declare(strict_types=1);

namespace App\Domain\Seerah\Models;

use App\Domain\Shared\Traits\HasLocalizedContent;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * SeerahEventTimelineItem Model
 *
 * A single sub-point in the inner timeline of a Seerah event
 * (the 4–6 expandable moments shown on the event single pages).
 *
 * @property int $id
 * @property int $seerah_event_id
 * @property int $display_order
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class SeerahEventTimelineItem extends Model
{
    use HasFactory, HasLocalizedContent;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'seerah_event_timeline_items';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'seerah_event_id',
        'display_order',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'seerah_event_id' => 'integer',
        'display_order' => 'integer',
    ];

    /**
     * Get the translation model class.
     */
    public function getTranslationModelClass(): string
    {
        return SeerahEventTimelineItemTranslation::class;
    }

    /**
     * Get the list of translatable field names.
     */
    public function getTranslatableFields(): array
    {
        return ['title', 'description'];
    }

    /**
     * Get the event this timeline item belongs to
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(SeerahEvent::class, 'seerah_event_id');
    }

    /**
     * Scope: Order by display order
     *
     * @param  Builder  $query
     * @return Builder
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('display_order');
    }
}
