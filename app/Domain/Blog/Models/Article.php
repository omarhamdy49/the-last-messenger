<?php

declare(strict_types=1);

namespace App\Domain\Blog\Models;

use App\Domain\Blog\Enums\ArticleStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Domain\Shared\Traits\HasInteractions;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\Translatable\HasTranslations;

/**
 * Article Model
 * Represents blog posts and Seerah content
 *
 * @property int $id
 * @property string $slug
 * @property int $user_id
 * @property int|null $category_id
 * @property int|null $series_id
 * @property array $title
 * @property array|null $excerpt
 * @property array $content
 * @property string|null $featured_image
 * @property int $reading_time_minutes
 * @property int $views_count
 * @property int $favorites_count
 * @property ArticleStatus $status
 * @property \Carbon\Carbon|null $published_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 */
class Article extends Model implements HasMedia
{
    use HasFactory, HasTranslations, InteractsWithMedia, SoftDeletes, HasInteractions;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'slug',
        'user_id',
        'category_id',
        'series_id',
        'title',
        'excerpt',
        'content',
        'featured_image',
        'reading_time_minutes',
        'views_count',
        'favorites_count',
        'status',
        'published_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'title' => 'array',
            'excerpt' => 'array',
            'content' => 'array',
            'status' => ArticleStatus::class,
            'published_at' => 'datetime',
        ];
    }

    /**
     * Translatable attributes
     *
     * @var array<string>
     */
    public array $translatable = ['title', 'excerpt', 'content'];

    /**
     * Boot the model
     */
    protected static function boot(): void
    {
        parent::boot();

        // Auto-increment views when accessed
        static::retrieved(function (Article $article) {
            // This can be handled in service layer for better control
        });
    }

    /**
     * Get the author of the article
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the category
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get the series
     */
    public function series(): BelongsTo
    {
        return $this->belongsTo(Series::class);
    }

    /**
     * Get all tags
     */
    public function tags(): MorphToMany
    {
        return $this->morphToMany(Tag::class, 'taggable', 'taggables');
    }

    /**
     * Get all favorites
     */
    public function favorites(): MorphMany
    {
        return $this->morphMany(Favorite::class, 'favorable');
    }

    // Note: interactions() method provided by HasInteractions trait

    /**
     * Scope: Published articles only
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', ArticleStatus::PUBLISHED)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    /**
     * Scope: Featured articles
     */
    public function scopeFeatured(Builder $query): Builder
    {
        return $query->published()
            ->whereNotNull('featured_image')
            ->orderByDesc('published_at');
    }

    /**
     * Scope: Filter by category
     */
    public function scopeByCategory(Builder $query, int $categoryId): Builder
    {
        return $query->where('category_id', $categoryId);
    }

    /**
     * Scope: Filter by series
     */
    public function scopeBySeries(Builder $query, int $seriesId): Builder
    {
        return $query->where('series_id', $seriesId);
    }

    /**
     * Scope: Popular articles (by views)
     */
    public function scopePopular(Builder $query, int $limit = 10): Builder
    {
        return $query->published()
            ->orderByDesc('views_count')
            ->limit($limit);
    }

    /**
     * Scope: Recent articles
     */
    public function scopeRecent(Builder $query, int $limit = 10): Builder
    {
        return $query->published()
            ->orderByDesc('published_at')
            ->limit($limit);
    }

    /**
     * Scope: Full-text search
     */
    public function scopeSearch(Builder $query, string $search): Builder
    {
        return $query->whereRaw(
            'MATCH(title_searchable, excerpt_searchable) AGAINST(? IN BOOLEAN MODE)',
            [$search]
        );
    }

    /**
     * Increment views count
     */
    public function incrementViews(): void
    {
        $this->increment('views_count');
    }

    /**
     * Increment favorites count
     */
    public function incrementFavorites(): void
    {
        $this->increment('favorites_count');
    }

    /**
     * Decrement favorites count
     */
    public function decrementFavorites(): void
    {
        $this->decrement('favorites_count');
    }

    /**
     * Check if article is published
     */
    public function isPublished(): bool
    {
        return $this->status === ArticleStatus::PUBLISHED
            && $this->published_at !== null
            && $this->published_at->isPast();
    }

    /**
     * Publish the article
     */
    public function publish(): bool
    {
        return $this->update([
            'status' => ArticleStatus::PUBLISHED,
            'published_at' => $this->published_at ?? now(),
        ]);
    }

    /**
     * Unpublish the article (set to draft)
     */
    public function unpublish(): bool
    {
        return $this->update([
            'status' => ArticleStatus::DRAFT,
        ]);
    }

    /**
     * Archive the article
     */
    public function archive(): bool
    {
        return $this->update([
            'status' => ArticleStatus::ARCHIVED,
        ]);
    }

    /**
     * Get title in specified language
     */
    public function getTitleInLanguage(string $language): ?string
    {
        return $this->title[$language] ?? $this->title['ar'] ?? null;
    }

    /**
     * Get excerpt in specified language
     */
    public function getExcerptInLanguage(string $language): ?string
    {
        return $this->excerpt[$language] ?? $this->excerpt['ar'] ?? null;
    }

    /**
     * Get content in specified language
     */
    public function getContentInLanguage(string $language): ?string
    {
        return $this->content[$language] ?? $this->content['ar'] ?? null;
    }

    /**
     * Register media collections
     */
    public function registerMediaCollections(): void
    {
        // Featured image collection (single file)
        $this->addMediaCollection('featured_image')
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/jpg', 'image/png', 'image/webp', 'image/gif'])
            ->maxFilesize(10 * 1024 * 1024) // 10MB
            ->useDisk('public');

        // OG image collection (single file) for social media sharing
        $this->addMediaCollection('og_image')
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/jpg', 'image/png', 'image/webp'])
            ->maxFilesize(5 * 1024 * 1024) // 5MB
            ->useDisk('public');

        // Gallery collection (multiple files)
        $this->addMediaCollection('gallery')
            ->acceptsMimeTypes(['image/jpeg', 'image/jpg', 'image/png', 'image/webp', 'image/gif'])
            ->maxFilesize(10 * 1024 * 1024) // 10MB
            ->useDisk('public');
    }

    /**
     * Register media conversions
     */
    public function registerMediaConversions(?\Spatie\MediaLibrary\MediaCollections\Models\Media $media = null): void
    {
        // Thumbnail conversion (150x150 crop-center)
        $this->addMediaConversion('thumbnail')
            ->width(150)
            ->height(150)
            ->crop('crop-center')
            ->sharpen(10)
            ->optimize()
            ->performOnCollections('featured_image', 'gallery')
            ->nonQueued();

        // Medium conversion (800x600 contain)
        $this->addMediaConversion('medium')
            ->width(800)
            ->height(600)
            ->fit('contain')
            ->sharpen(10)
            ->optimize()
            ->performOnCollections('featured_image', 'gallery')
            ->nonQueued();

        // Large conversion (1200x800 contain)
        $this->addMediaConversion('large')
            ->width(1200)
            ->height(800)
            ->fit('contain')
            ->sharpen(10)
            ->optimize()
            ->performOnCollections('featured_image', 'gallery')
            ->nonQueued();

        // OG image conversion (1200x630 crop for social media)
        $this->addMediaConversion('og')
            ->width(1200)
            ->height(630)
            ->fit('crop')
            ->sharpen(10)
            ->optimize()
            ->performOnCollections('og_image')
            ->nonQueued();
    }

    /**
     * Get featured image URL (accessor with backward compatibility)
     *
     * @return string|null
     */
    public function getFeaturedImageUrlAttribute(): ?string
    {
        return $this->getFirstMediaUrl('featured_image') ?: $this->featured_image;
    }

    /**
     * Get featured image thumbnail URL
     *
     * @return string|null
     */
    public function getFeaturedImageThumbnailAttribute(): ?string
    {
        return $this->getFirstMediaUrl('featured_image', 'thumbnail') ?: null;
    }

    /**
     * Get OG image URL (accessor with backward compatibility)
     *
     * @return string|null
     */
    public function getOgImageUrlAttribute(): ?string
    {
        $mediaUrl = $this->getFirstMediaUrl('og_image', 'og');
        if ($mediaUrl) {
            return $mediaUrl;
        }

        // Fallback to featured image if no OG image
        return $this->getFirstMediaUrl('featured_image', 'og') ?: $this->featured_image;
    }

    /**
     * Get gallery images with conversions
     *
     * @return array
     */
    public function getGalleryImagesAttribute(): array
    {
        return $this->getMedia('gallery')->map(fn($media) => [
            'id' => $media->id,
            'url' => $media->getUrl(),
            'thumbnail' => $media->getUrl('thumbnail'),
            'medium' => $media->getUrl('medium'),
            'large' => $media->getUrl('large'),
            'alt' => $media->getCustomProperty('alt_text'),
            'caption' => $media->getCustomProperty('caption'),
        ])->toArray();
    }
}
