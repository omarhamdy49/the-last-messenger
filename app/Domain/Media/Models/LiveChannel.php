<?php

declare(strict_types=1);

namespace App\Domain\Media\Models;

use App\Domain\Media\Enums\LiveStatus;
use App\Domain\Shared\Traits\HasLocalizedContent;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * LiveChannel Model
 *
 * A live broadcast channel (e.g. a 24/7 Quran recitation stream).
 * Thumbnail is handled via Spatie MediaLibrary; translations (title,
 * description) live in live_channel_translations via HasLocalizedContent.
 *
 * @property int $id
 * @property string $slug
 * @property string|null $streaming_url
 * @property LiveStatus $status
 * @property bool $is_published
 * @property int $display_order
 * @property int $views_count
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class LiveChannel extends Model implements HasMedia
{
    use HasFactory, HasLocalizedContent, InteractsWithMedia;

    protected $table = 'live_channels';

    protected $fillable = [
        'slug',
        'streaming_url',
        'status',
        'is_published',
        'display_order',
        'views_count',
    ];

    protected $casts = [
        'status' => LiveStatus::class,
        'is_published' => 'boolean',
        'display_order' => 'integer',
        'views_count' => 'integer',
    ];

    // ------------------------------------------------------------------
    // HasLocalizedContent contract
    // ------------------------------------------------------------------

    public function getTranslationModelClass(): string
    {
        return LiveChannelTranslation::class;
    }

    public function getTranslatableFields(): array
    {
        return ['title', 'description'];
    }

    public function getTranslationForeignKey(): string
    {
        return 'live_channel_id';
    }

    // ------------------------------------------------------------------
    // Accessors
    // ------------------------------------------------------------------

    public function getThumbnailUrlAttribute(): ?string
    {
        try {
            return $this->getFirstMediaUrl('thumbnail') ?: null;
        } catch (\Throwable) {
            return null;
        }
    }

    /** Small admin/list thumbnail: 'thumb' conversion with original fallback. */
    public function getThumbUrlAttribute(): ?string
    {
        try {
            return $this->getFirstMediaUrl('thumbnail', 'thumb')
                ?: ($this->getFirstMediaUrl('thumbnail') ?: null);
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Stream URL normalized to an embeddable form. Bare YouTube watch/short
     * links become /embed/ URLs (a watch URL inside an iframe is refused by
     * YouTube); anything else passes through unchanged. Muted autoplay:
     * browsers block audible autoplay, so the visitor unmutes in the player.
     */
    public function getEmbedUrlAttribute(): ?string
    {
        $url = $this->streaming_url;

        if ($url === null || trim($url) === '') {
            return null;
        }

        if (preg_match('#(?:youtube\.com/(?:embed/|watch\?v=|live/)|youtu\.be/)([A-Za-z0-9_-]{11})#', $url, $m) === 1) {
            return "https://www.youtube.com/embed/{$m[1]}?autoplay=1&mute=1";
        }

        return $url;
    }

    public function isLive(): bool
    {
        return $this->status === LiveStatus::Live;
    }

    public function incrementViews(int $amount = 1): void
    {
        $this->increment('views_count', $amount);
    }

    // ------------------------------------------------------------------
    // Scopes
    // ------------------------------------------------------------------

    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    public function scopeOrdered($query)
    {
        // Live channels first, then manual order.
        return $query->orderByRaw("CASE WHEN status = 'live' THEN 0 ELSE 1 END")
            ->orderBy('display_order')
            ->orderByDesc('created_at');
    }

    // ------------------------------------------------------------------
    // MediaLibrary — single landscape thumbnail per channel
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
