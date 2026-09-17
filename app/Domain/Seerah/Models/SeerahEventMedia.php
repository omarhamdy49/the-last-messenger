<?php

declare(strict_types=1);

namespace App\Domain\Seerah\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A media-library item attached to a Seerah event — the same shape as
 * `articles_media`.
 *
 * Holds the id only. Seerah may not import `App\Models\Media`, so the item is
 * resolved through the Shared `MediaLibraryLookup` contract.
 *
 * @property int $id
 * @property int $event_id
 * @property int $media_id
 */
class SeerahEventMedia extends Model
{
    protected $table = 'seerah_events_media';

    protected $fillable = [
        'event_id',
        'media_id',
    ];

    protected $casts = [
        'event_id' => 'integer',
        'media_id' => 'integer',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(SeerahEvent::class, 'event_id');
    }
}
