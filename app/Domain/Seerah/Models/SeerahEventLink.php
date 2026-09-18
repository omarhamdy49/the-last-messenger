<?php

declare(strict_types=1);

namespace App\Domain\Seerah\Models;

use App\Domain\Shared\Enums\LinkedContentType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A link from a Seerah event to content in another module (verse, hadith,
 * companion, article, media episode, tag, category).
 *
 * Holds the target id only; titles and URLs come from the Shared
 * ContentReferenceLookup so Seerah never imports the target module.
 *
 * @property int $id
 * @property int $event_id
 * @property LinkedContentType $type
 * @property int $target_id
 * @property int $order
 */
class SeerahEventLink extends Model
{
    protected $table = 'seerah_event_links';

    protected $fillable = [
        'event_id',
        'type',
        'target_id',
        'order',
    ];

    protected $casts = [
        'event_id' => 'integer',
        'type' => LinkedContentType::class,
        'target_id' => 'integer',
        'order' => 'integer',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(SeerahEvent::class, 'event_id');
    }
}
