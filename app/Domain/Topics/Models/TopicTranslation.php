<?php

declare(strict_types=1);

namespace App\Domain\Topics\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * TopicTranslation Model
 *
 * Stores per-locale title + description for a Topic.
 * Mirrors the SeerahEventTranslation pattern (dedicated *_translations table).
 *
 * @property int $id
 * @property int $topic_id
 * @property string $locale
 * @property string|null $title
 * @property string|null $description
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property-read Topic $topic
 */
class TopicTranslation extends Model
{
    protected $table = 'topic_translations';

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'topic_id',
        'locale',
        'title',
        'description',
        'is_ready',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'topic_id' => 'integer',
        'is_ready' => 'boolean',
    ];

    public function topic(): BelongsTo
    {
        return $this->belongsTo(Topic::class, 'topic_id');
    }

    /**
     * Scope: filter by locale.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByLocale($query, string $locale)
    {
        return $query->where('locale', $locale);
    }
}
