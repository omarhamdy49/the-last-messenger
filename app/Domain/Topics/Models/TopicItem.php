<?php

declare(strict_types=1);

namespace App\Domain\Topics\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * TopicItem Model
 *
 * Polymorphic link between a Topic and a piece of content (Verse / Hadith / Article).
 *
 * The `itemable_type` column stores a SHORT, STABLE morph alias ("verse", "hadith",
 * "article") rather than a fully-qualified class name. The alias↔class mapping is
 * registered once at boot via Relation::enforceMorphMap() in TopicsServiceProvider —
 * the same deptrac-safe pattern used by FavorableRegistry. This keeps this Topics
 * model free of compile-time imports of other domain modules' model classes.
 *
 * @property int $id
 * @property int $topic_id
 * @property string $itemable_type Morph alias: verse|hadith|article
 * @property int $itemable_id
 * @property int $sort_order
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Topic $topic
 * @property-read Model|null $itemable
 */
class TopicItem extends Model
{
    protected $table = 'topic_items';

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'topic_id',
        'itemable_type',
        'itemable_id',
        'sort_order',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'topic_id' => 'integer',
        'itemable_id' => 'integer',
        'sort_order' => 'integer',
    ];

    public function topic(): BelongsTo
    {
        return $this->belongsTo(Topic::class, 'topic_id');
    }

    /**
     * The linked content model, resolved through the morph map.
     */
    public function itemable(): MorphTo
    {
        return $this->morphTo();
    }
}
