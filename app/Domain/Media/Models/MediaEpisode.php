<?php

declare(strict_types=1);

namespace App\Domain\Media\Models;

use App\Domain\Media\Support\YouTubeUrl;
use App\Domain\Shared\Traits\HasLocalizedContent;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * MediaEpisode Model
 *
 * A single video episode belonging to a MediaProgram.
 * video_url stores a YouTube embed URL (https://www.youtube.com/embed/{id}).
 *
 * @property int $id
 * @property int $program_id
 * @property int $season_number
 * @property int $episode_number
 * @property string $slug
 * @property string|null $video_url
 * @property int|null $duration_seconds
 * @property int $views_count
 * @property bool $is_featured
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class MediaEpisode extends Model implements HasMedia
{
    use HasFactory, HasLocalizedContent, \Spatie\MediaLibrary\InteractsWithMedia;

    protected $table = 'media_episodes';

    protected $fillable = [
        'program_id',
        'season_number',
        'episode_number',
        'slug',
        'video_url',
        'duration_seconds',
        'views_count',
        'is_featured',
        'is_published',
        'publish_at',
    ];

    protected $casts = [
        'program_id' => 'integer',
        'season_number' => 'integer',
        'episode_number' => 'integer',
        'duration_seconds' => 'integer',
        'views_count' => 'integer',
        'is_featured' => 'boolean',
        'is_published' => 'boolean',
        'publish_at' => 'datetime',
    ];

    // ------------------------------------------------------------------
    // HasLocalizedContent contract
    // ------------------------------------------------------------------

    public function getTranslationModelClass(): string
    {
        return MediaEpisodeTranslation::class;
    }

    public function getTranslatableFields(): array
    {
        return ['title', 'description', 'transcript', 'seo_title', 'seo_description', 'seo_keywords'];
    }

    public function getTranslationForeignKey(): string
    {
        return 'media_episode_id';
    }

    // ------------------------------------------------------------------
    // Relations
    // ------------------------------------------------------------------

    public function program(): BelongsTo
    {
        return $this->belongsTo(MediaProgram::class, 'program_id');
    }

    public function watchProgress(): HasMany
    {
        return $this->hasMany(EpisodeWatchProgress::class, 'episode_id');
    }

    // ------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------

    public function getThumbnailUrl(): ?string
    {
        try {
            return $this->getFirstMediaUrl('thumbnail')
                ?: $this->youtubeThumbnailUrl();
        } catch (\Throwable) {
            return null;
        }
    }

    /** Small admin-list thumbnail: 'thumb' conversion with original fallback. */
    public function getThumbUrlAttribute(): ?string
    {
        try {
            return $this->getFirstMediaUrl('thumbnail', 'thumb')
                ?: ($this->getFirstMediaUrl('thumbnail') ?: $this->youtubeThumbnailUrl());
        } catch (\Throwable) {
            return null;
        }
    }

    /** YouTube CDN thumbnail derived from video_url; null for non-YouTube episodes. */
    public function youtubeThumbnailUrl(): ?string
    {
        $videoId = YouTubeUrl::videoId($this->video_url);

        return $videoId !== null ? YouTubeUrl::thumbnailUrl($videoId) : null;
    }

    public function getDurationFormattedAttribute(): string
    {
        if (! $this->duration_seconds) {
            return '';
        }

        $hours = intdiv($this->duration_seconds, 3600);
        $minutes = intdiv($this->duration_seconds % 3600, 60);
        $seconds = $this->duration_seconds % 60;

        return sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
    }

    public function incrementViews(int $amount = 1): void
    {
        $this->increment('views_count', $amount);
    }

    // ------------------------------------------------------------------
    // Scopes
    // ------------------------------------------------------------------

    public function scopeOrdered($query)
    {
        return $query->orderBy('season_number')->orderBy('episode_number');
    }

    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    /** Admin-facing lifecycle label: published | scheduled | draft. */
    public function publishStatus(): string
    {
        if ($this->is_published) {
            return 'published';
        }

        return $this->publish_at !== null ? 'scheduled' : 'draft';
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    // ------------------------------------------------------------------
    // MediaLibrary — thumbnail per episode
    // ------------------------------------------------------------------

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('thumbnail')
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/jpg', 'image/png', 'image/webp'])
            ->useDisk('public');
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->fit(Fit::Crop, 400, 225)
            ->optimize()
            ->performOnCollections('thumbnail')
            ->nonQueued();
    }
}
