<?php

declare(strict_types=1);

namespace App\Domain\Seerah\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * SeerahEventTranslation Model
 *
 * Stores per-locale translations for SeerahEvent.
 *
 * @property int $id
 * @property int $event_id
 * @property string $locale
 * @property string|null $title
 * @property string|null $description
 * @property string|null $content
 * @property string|null $dispute_note
 * @property array<int, array{key: string, value: string}>|null $metadata
 * @property array<int, array{title?: string, author?: string, reference?: string}>|null $sources
 * @property bool $is_ready
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read SeerahEvent $event
 */
class SeerahEventTranslation extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'seerah_event_translations';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'event_id',
        'locale',
        'title',
        'description',
        'content',
        'dispute_note',
        'metadata',
        'sources',
        'is_ready',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'event_id' => 'integer',
        'is_ready' => 'boolean',
        'metadata' => 'array',
        'sources' => 'array',
    ];

    /**
     * Get the parent seerah event.
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(SeerahEvent::class, 'event_id');
    }

    /**
     * Scope: Filter by locale.
     *
     * @param  Builder  $query
     * @return Builder
     */
    public function scopeByLocale($query, string $locale)
    {
        return $query->where('locale', $locale);
    }

    /**
     * Scope: Only ready translations.
     *
     * @param  Builder  $query
     * @return Builder
     */
    public function scopeReady($query)
    {
        return $query->where('is_ready', true);
    }

    /**
     * Check if this translation is RTL.
     */
    public function isRtl(): bool
    {
        return in_array($this->locale, ['ar', 'ur']);
    }
}
