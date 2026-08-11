<?php

declare(strict_types=1);

namespace App\Domain\Media\Models;

use App\Domain\Media\Enums\MaturityRating;
use App\Domain\Media\Enums\ProgramCategory;
use App\Domain\Media\Support\YouTubeUrl;
use App\Domain\Shared\Models\SpatieMedia;
use App\Domain\Shared\Traits\HasLocalizedContent;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * MediaProgram Model
 *
 * Represents a video program or series (e.g. documentary, seerah series).
 * Media (poster/banner) is handled via Spatie MediaLibrary collections.
 * Translations are in the media_program_translations table via HasLocalizedContent.
 *
 * @property int $id
 * @property string $slug
 * @property ProgramCategory $category
 * @property MaturityRating $maturity_rating
 * @property int|null $year
 * @property int $total_seasons
 * @property int $total_episodes
 * @property float $rating
 * @property bool $is_featured
 * @property bool $is_trending
 * @property bool $is_published
 * @property Carbon|null $publish_at
 * @property int $views_count
 * @property int $display_order
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class MediaProgram extends Model implements HasMedia
{
    use HasFactory, HasLocalizedContent, InteractsWithMedia;

    protected $table = 'media_programs';

    protected $fillable = [
        'slug',
        'youtube_playlist_id',
        'category',
        'maturity_rating',
        'year',
        'total_seasons',
        'total_episodes',
        'rating',
        'is_featured',
        'is_trending',
        'is_published',
        'publish_at',
        'views_count',
        'display_order',
    ];

    protected $casts = [
        'category' => ProgramCategory::class,
        'maturity_rating' => MaturityRating::class,
        'year' => 'integer',
        'total_seasons' => 'integer',
        'total_episodes' => 'integer',
        'rating' => 'decimal:1',
        'is_featured' => 'boolean',
        'is_trending' => 'boolean',
        'is_published' => 'boolean',
        'publish_at' => 'datetime',
        'views_count' => 'integer',
        'display_order' => 'integer',
    ];

    // ------------------------------------------------------------------
    // HasLocalizedContent contract
    // ------------------------------------------------------------------

    public function getTranslationModelClass(): string
    {
        return MediaProgramTranslation::class;
    }

    public function getTranslatableFields(): array
    {
        return ['title', 'description', 'host', 'seo_title', 'seo_description', 'seo_keywords'];
    }

    public function getTranslationForeignKey(): string
    {
        return 'media_program_id';
    }

    protected static function booted(): void
    {
        // Episode rows die via the DB-level cascade, which skips Eloquent
        // events — so their attachments (files + conversions) would be
        // orphaned. Delete them before the cascade runs; the program's own
        // poster/banner are cleaned up by InteractsWithMedia itself.
        static::deleting(function (self $program): void {
            $episodeIds = $program->episodes()->pluck('id');

            if ($episodeIds->isEmpty()) {
                return;
            }

            SpatieMedia::query()
                ->where('model_type', (new MediaEpisode)->getMorphClass())
                ->whereIn('model_id', $episodeIds)
                ->get()
                ->each
                ->delete();
        });
    }

    // ------------------------------------------------------------------
    // Relations
    // ------------------------------------------------------------------

    public function episodes(): HasMany
    {
        return $this->hasMany(MediaEpisode::class, 'program_id')
            ->orderBy('season_number')
            ->orderBy('episode_number');
    }

    // ------------------------------------------------------------------
    // Mutators / Accessors
    // ------------------------------------------------------------------

    public function getPosterUrlAttribute(): ?string
    {
        try {
            return $this->getFirstMediaUrl('poster') ?: $this->youtubeThumbnailUrl();
        } catch (\Throwable) {
            return null;
        }
    }

    public function getBannerUrlAttribute(): ?string
    {
        try {
            return $this->getFirstMediaUrl('banner') ?: $this->youtubeThumbnailUrl();
        } catch (\Throwable) {
            return null;
        }
    }

    /** Poster thumbnail conversion, falling back to the original when missing. */
    public function getPosterThumbUrlAttribute(): ?string
    {
        try {
            return $this->getFirstMediaUrl('poster', 'thumbnail')
                ?: ($this->getFirstMediaUrl('poster') ?: $this->youtubeThumbnailUrl());
        } catch (\Throwable) {
            return null;
        }
    }

    /** YouTube CDN thumbnail of the first episode; null when no YouTube episodes. */
    public function youtubeThumbnailUrl(): ?string
    {
        $videoUrl = $this->relationLoaded('episodes')
            ? $this->episodes->first()?->video_url
            : $this->episodes()->value('video_url');
        $videoId = YouTubeUrl::videoId($videoUrl);

        return $videoId !== null ? YouTubeUrl::thumbnailUrl($videoId) : null;
    }

    // ------------------------------------------------------------------
    // Scopes
    // ------------------------------------------------------------------

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

    public function scopeTrending($query)
    {
        return $query->where('is_trending', true);
    }

    public function scopeByCategory($query, ProgramCategory|string $category)
    {
        $value = $category instanceof ProgramCategory ? $category->value : $category;

        return $query->where('category', $value);
    }

    public function scopePopular($query)
    {
        return $query->orderByDesc('views_count');
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('display_order')->orderByDesc('created_at');
    }

    public function incrementViews(int $amount = 1): void
    {
        $this->increment('views_count', $amount);
    }

    // ------------------------------------------------------------------
    // MediaLibrary
    // ------------------------------------------------------------------

    public function registerMediaCollections(): void
    {
        // Size limits are enforced by FormRequest validation (max:10240) —
        // MediaLibrary v11 collections have no maxFilesize() method.
        $this->addMediaCollection('poster')
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/jpg', 'image/png', 'image/webp'])
            ->useDisk('public');

        $this->addMediaCollection('banner')
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/jpg', 'image/png', 'image/webp'])
            ->useDisk('public');
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumbnail')
            ->fit(Fit::Crop, 300, 450)
            ->optimize()
            ->performOnCollections('poster')
            ->nonQueued();

        $this->addMediaConversion('medium')
            ->fit(Fit::Contain, 600, 338)
            ->optimize()
            ->performOnCollections('banner')
            ->nonQueued();
    }
}
