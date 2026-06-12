<?php

declare(strict_types=1);

namespace App\Domain\Media\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * MediaEpisodeTranslation Model
 *
 * Per-locale translations for MediaEpisode.
 *
 * @property int $id
 * @property int $media_episode_id
 * @property string $locale
 * @property string|null $title
 * @property string|null $description
 * @property bool $is_ready
 */
class MediaEpisodeTranslation extends Model
{
    protected $table = 'media_episode_translations';

    protected $fillable = [
        'media_episode_id',
        'locale',
        'title',
        'description',
        'transcript',
        'seo_title',
        'seo_description',
        'seo_keywords',
        'is_ready',
    ];

    protected $casts = [
        'media_episode_id' => 'integer',
        'is_ready' => 'boolean',
    ];

    public function episode(): BelongsTo
    {
        return $this->belongsTo(MediaEpisode::class, 'media_episode_id');
    }

    public function scopeByLocale($query, string $locale)
    {
        return $query->where('locale', $locale);
    }
}
