<?php

declare(strict_types=1);

namespace App\Domain\Seerah\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * SeerahEventTranslation Model
 *
 * Stores per-locale translations for SeerahEvent.
 *
 * @property int $id
 * @property int $seerah_event_id
 * @property string $locale
 * @property string|null $title
 * @property string|null $description
 * @property string|null $content
 * @property bool $is_ready
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property-read SeerahEvent $seerahEvent
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
        'seerah_event_id',
        'locale',
        'title',
        'description',
        'content',
        'lessons',
        'sources',
        'dispute_note',
        'is_ready',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'seerah_event_id' => 'integer',
        'is_ready' => 'boolean',
        // [{title, description}] and [{title, author, reference}] per locale.
        'lessons' => 'array',
        'sources' => 'array',
    ];

    /**
     * Get the parent seerah event.
     */
    public function seerahEvent(): BelongsTo
    {
        return $this->belongsTo(SeerahEvent::class, 'seerah_event_id');
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

    /**
     * Scope: Only ready translations.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeReady($query)
    {
        return $query->where('is_ready', true);
    }

    /**
     * Scope: Search title field.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSearchTitle($query, string $term)
    {
        return $query->where('title', 'LIKE', "%{$term}%");
    }

    /**
     * Scope: Search description field.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSearchDescription($query, string $term)
    {
        return $query->where('description', 'LIKE', "%{$term}%");
    }

    /**
     * Check if this translation is RTL.
     */
    public function isRtl(): bool
    {
        return in_array($this->locale, ['ar', 'ur']);
    }
}
