<?php

declare(strict_types=1);

namespace App\Domain\Seerah\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One image in a Seerah event's gallery — the same shape as `articles_gallery`.
 *
 * `image` is the public-disk copy; `media_id` is the media-library item it was
 * picked from, so the picker round-trips on edit.
 *
 * @property int $id
 * @property int $event_id
 * @property string $image
 * @property int|null $media_id
 * @property array<string, string>|null $caption
 * @property int $order
 */
class SeerahEventGalleryItem extends Model
{
    protected $table = 'seerah_events_gallery';

    protected $fillable = [
        'event_id',
        'image',
        'media_id',
        'caption',
        'order',
    ];

    protected $casts = [
        'event_id' => 'integer',
        'media_id' => 'integer',
        'caption' => 'array',
        'order' => 'integer',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(SeerahEvent::class, 'event_id');
    }

    public function captionFor(?string $locale = null): ?string
    {
        $locale ??= app()->getLocale();

        return filled($this->caption[$locale] ?? null)
            ? $this->caption[$locale]
            : ($this->caption['ar'] ?? null);
    }
}
