<?php

declare(strict_types=1);

namespace App\Domain\Media\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * LiveChannelTranslation Model
 *
 * Per-locale translations for LiveChannel.
 *
 * @property int $id
 * @property int $live_channel_id
 * @property string $locale
 * @property string|null $title
 * @property string|null $description
 * @property bool $is_ready
 */
class LiveChannelTranslation extends Model
{
    protected $table = 'live_channel_translations';

    protected $fillable = [
        'live_channel_id',
        'locale',
        'title',
        'description',
        'is_ready',
    ];

    protected $casts = [
        'live_channel_id' => 'integer',
        'is_ready' => 'boolean',
    ];

    public function channel(): BelongsTo
    {
        return $this->belongsTo(LiveChannel::class, 'live_channel_id');
    }

    public function scopeByLocale($query, string $locale)
    {
        return $query->where('locale', $locale);
    }

    public function isRtl(): bool
    {
        return in_array($this->locale, ['ar', 'ur']);
    }
}
