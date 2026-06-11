<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media as SpatieMedia;

class Media extends Model implements HasMedia
{
    use InteractsWithMedia;
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'public_key',
        'filename',
        'original_filename',
        'path',
        'mime_type',
        'size',
        'width',
        'height',
        'disk',
        'alt_text',
        'caption',
        'metadata',
        'type',
        'mediable_type',
        'mediable_id',
        'collection_name',
        'tags',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'metadata' => 'array',
        'tags' => 'array',
        'size' => 'integer',
        'width' => 'integer',
        'height' => 'integer',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'url',
        'thumbnail_url',
        'preview_url',
        'human_readable_size',
    ];

    /**
     * Get the parent mediable model (Article, Sahabi, etc.)
     */
    public function mediable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Register media collections
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('default')
            ->useDisk('public');

        $this->addMediaCollection('images')
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/gif', 'image/webp'])
            ->useDisk('public');

        $this->addMediaCollection('videos')
            ->acceptsMimeTypes(['video/mp4', 'video/webm', 'video/avi'])
            ->useDisk('public');

        $this->addMediaCollection('documents')
            ->acceptsMimeTypes(['application/pdf'])
            ->useDisk('public');
    }

    /**
     * Register media conversions
     */
    public function registerMediaConversions(?SpatieMedia $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->width(300)
            ->height(300)
            ->sharpen(10)
            ->nonQueued();

        $this->addMediaConversion('preview')
            ->width(800)
            ->height(600)
            ->sharpen(10)
            ->nonQueued();

        $this->addMediaConversion('large')
            ->width(1920)
            ->height(1080)
            ->sharpen(10)
            ->nonQueued();
    }

    /**
     * Get the full URL of the media file
     */
    public function getUrlAttribute(): string
    {
        if (app('router')->has('admin.media-library.file')) {
            return route('admin.media-library.file', ['media' => $this->publicRouteKey()]);
        }

        return Storage::disk($this->disk ?? 'local')->url($this->path);
    }

    /**
     * Get the thumbnail URL
     */
    public function getThumbnailUrlAttribute(): ?string
    {
        if ($this->isImage() && app('router')->has('admin.media-library.variant')) {
            if (Arr::has($this->metadata ?? [], 'variants.thumbnail')) {
                return route('admin.media-library.variant', ['media' => $this->publicRouteKey(), 'variant' => 'thumbnail']);
            }

            if (Arr::has($this->metadata ?? [], 'variants.medium')) {
                return route('admin.media-library.variant', ['media' => $this->publicRouteKey(), 'variant' => 'medium']);
            }
        }

        return match ($this->type) {
            'audio' => asset('images/audio-placeholder.png'),
            'document' => asset('images/pdf-placeholder.png'),
            default => asset('images/placeholder.png'),
        };
    }

    public function getPreviewUrlAttribute(): ?string
    {
        if ($this->isImage() && app('router')->has('admin.media-library.variant')) {
            if (Arr::has($this->metadata ?? [], 'variants.medium')) {
                return route('admin.media-library.variant', ['media' => $this->publicRouteKey(), 'variant' => 'medium']);
            }

            if (Arr::has($this->metadata ?? [], 'variants.large')) {
                return route('admin.media-library.variant', ['media' => $this->publicRouteKey(), 'variant' => 'large']);
            }
        }

        return $this->url;
    }

    /**
     * Get human-readable file size
     */
    public function getHumanReadableSizeAttribute(): string
    {
        $bytes = $this->size;
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2).' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2).' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2).' KB';
        } elseif ($bytes > 1) {
            return $bytes.' bytes';
        } elseif ($bytes == 1) {
            return $bytes.' byte';
        } else {
            return '0 bytes';
        }
    }

    /**
     * Get the full path of the media file
     */
    public function getFullPathAttribute(): string
    {
        return Storage::disk($this->disk ?? 'public')->path($this->path);
    }

    /**
     * Check if the media is an image
     */
    public function isImage(): bool
    {
        return str_starts_with($this->mime_type, 'image/');
    }

    /**
     * Check if the media is a video
     */
    public function isVideo(): bool
    {
        return str_starts_with($this->mime_type, 'video/');
    }

    /**
     * Check if the media is a document
     */
    public function isDocument(): bool
    {
        return str_starts_with($this->mime_type, 'application/');
    }

    /**
     * Scope: Filter by type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope: Filter by collection
     */
    public function scopeInCollection($query, string $collection)
    {
        return $query->where('collection_name', $collection);
    }

    /**
     * Scope: Filter by mime type
     */
    public function scopeByMimeType($query, string $mimeType)
    {
        return $query->where('mime_type', 'like', $mimeType.'%');
    }

    /**
     * Scope: Search by filename or alt text
     */
    public function scopeSearch($query, ?string $searchTerm)
    {
        if (empty($searchTerm)) {
            return $query;
        }

        return $query->where(function ($q) use ($searchTerm) {
            $q->where('filename', 'like', "%{$searchTerm}%")
                ->orWhere('original_filename', 'like', "%{$searchTerm}%")
                ->orWhere('alt_text', 'like', "%{$searchTerm}%")
                ->orWhere('caption', 'like', "%{$searchTerm}%")
                ->orWhere('metadata', 'like', "%{$searchTerm}%")
                ->orWhere('tags', 'like', "%{$searchTerm}%");
        });
    }

    /**
     * Scope: Filter by tags
     */
    public function scopeWithTag($query, $tag)
    {
        return $query->whereJsonContains('tags', $tag);
    }

    /**
     * Scope: Filter by date range
     */
    public function scopeCreatedBetween($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * Scope: Filter by size range
     */
    public function scopeSizeBetween($query, $minSize, $maxSize)
    {
        return $query->whereBetween('size', [$minSize, $maxSize]);
    }

    /**
     * Scope: Images only
     */
    public function scopeImages($query)
    {
        return $query->where('mime_type', 'like', 'image/%');
    }

    /**
     * Scope: Videos only
     */
    public function scopeVideos($query)
    {
        return $query->where('mime_type', 'like', 'video/%');
    }

    /**
     * Scope: Documents only
     */
    public function scopeDocuments($query)
    {
        return $query->where('mime_type', 'like', 'application/%');
    }

    /**
     * Scope: Unused media (not attached to any model)
     */
    public function scopeUnused($query)
    {
        return $query->whereNull('mediable_type')->whereNull('mediable_id');
    }

    /**
     * Get usage count (how many times this media is used)
     */
    public function getUsageCountAttribute(): int
    {
        if (! $this->mediable_type || ! $this->mediable_id) {
            return 0;
        }

        return 1; // Can be enhanced to check multiple relationships
    }

    public function publicRouteKey(): string
    {
        if (filled($this->public_key)) {
            return $this->public_key;
        }

        $this->forceFill(['public_key' => (string) Str::ulid()])->saveQuietly();

        return (string) $this->public_key;
    }

    /**
     * Delete the media file from storage when the model is deleted
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function (self $media): void {
            if (blank($media->public_key)) {
                $media->public_key = (string) Str::ulid();
            }
        });

        static::deleting(function ($media) {
            $disk = $media->disk ?? 'local';

            if (Storage::disk($disk)->exists($media->path)) {
                Storage::disk($disk)->delete($media->path);
            }

            foreach (($media->metadata['variants'] ?? []) as $variant) {
                $variantPath = $variant['path'] ?? null;

                if ($variantPath && Storage::disk($disk)->exists($variantPath)) {
                    Storage::disk($disk)->delete($variantPath);
                }
            }

            foreach (($media->metadata['custom_variants'] ?? []) as $variant) {
                $variantPath = $variant['path'] ?? null;

                if ($variantPath && Storage::disk($disk)->exists($variantPath)) {
                    Storage::disk($disk)->delete($variantPath);
                }
            }
        });
    }
}
