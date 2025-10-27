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
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

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
    use HasFactory, SoftDeletes, InteractsWithMedia;

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
     *
     * @return BelongsTo
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the category
     *
     * @return BelongsTo
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get the series
     *
     * @return BelongsTo
     */
    public function series(): BelongsTo
    {
        return $this->belongsTo(Series::class);
    }

    /**
     * Get all tags
     *
     * @return MorphToMany
     */
    public function tags(): MorphToMany
    {
        return $this->morphToMany(Tag::class, 'taggable', 'taggables');
    }

    /**
     * Get all favorites
     *
     * @return MorphMany
     */
    public function favorites(): MorphMany
    {
        return $this->morphMany(Favorite::class, 'favorable');
    }

    /**
     * Get user interactions
     *
     * @return MorphMany
     */
    public function interactions(): MorphMany
    {
        return $this->morphMany(UserInteraction::class, 'interactable');
    }

    /**
     * Scope: Published articles only
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', ArticleStatus::PUBLISHED)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    /**
     * Scope: Featured articles
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeFeatured(Builder $query): Builder
    {
        return $query->published()
            ->whereNotNull('featured_image')
            ->orderByDesc('published_at');
    }

    /**
     * Scope: Filter by category
     *
     * @param Builder $query
     * @param int $categoryId
     * @return Builder
     */
    public function scopeByCategory(Builder $query, int $categoryId): Builder
    {
        return $query->where('category_id', $categoryId);
    }

    /**
     * Scope: Filter by series
     *
     * @param Builder $query
     * @param int $seriesId
     * @return Builder
     */
    public function scopeBySeries(Builder $query, int $seriesId): Builder
    {
        return $query->where('series_id', $seriesId);
    }

    /**
     * Scope: Popular articles (by views)
     *
     * @param Builder $query
     * @param int $limit
     * @return Builder
     */
    public function scopePopular(Builder $query, int $limit = 10): Builder
    {
        return $query->published()
            ->orderByDesc('views_count')
            ->limit($limit);
    }

    /**
     * Scope: Recent articles
     *
     * @param Builder $query
     * @param int $limit
     * @return Builder
     */
    public function scopeRecent(Builder $query, int $limit = 10): Builder
    {
        return $query->published()
            ->orderByDesc('published_at')
            ->limit($limit);
    }

    /**
     * Scope: Full-text search
     *
     * @param Builder $query
     * @param string $search
     * @return Builder
     */
    public function scopeSearch(Builder $query, string $search): Builder
    {
        return $query->whereRaw(
            "MATCH(title, excerpt) AGAINST(? IN BOOLEAN MODE)",
            [$search]
        );
    }

    /**
     * Increment views count
     *
     * @return void
     */
    public function incrementViews(): void
    {
        $this->increment('views_count');
    }

    /**
     * Increment favorites count
     *
     * @return void
     */
    public function incrementFavorites(): void
    {
        $this->increment('favorites_count');
    }

    /**
     * Decrement favorites count
     *
     * @return void
     */
    public function decrementFavorites(): void
    {
        $this->decrement('favorites_count');
    }

    /**
     * Check if article is published
     *
     * @return bool
     */
    public function isPublished(): bool
    {
        return $this->status === ArticleStatus::PUBLISHED
            && $this->published_at !== null
            && $this->published_at->isPast();
    }

    /**
     * Publish the article
     *
     * @return bool
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
     *
     * @return bool
     */
    public function unpublish(): bool
    {
        return $this->update([
            'status' => ArticleStatus::DRAFT,
        ]);
    }

    /**
     * Archive the article
     *
     * @return bool
     */
    public function archive(): bool
    {
        return $this->update([
            'status' => ArticleStatus::ARCHIVED,
        ]);
    }

    /**
     * Get title in specified language
     *
     * @param string $language
     * @return string|null
     */
    public function getTitleInLanguage(string $language): ?string
    {
        return $this->title[$language] ?? $this->title['ar'] ?? null;
    }

    /**
     * Get excerpt in specified language
     *
     * @param string $language
     * @return string|null
     */
    public function getExcerptInLanguage(string $language): ?string
    {
        return $this->excerpt[$language] ?? $this->excerpt['ar'] ?? null;
    }

    /**
     * Get content in specified language
     *
     * @param string $language
     * @return string|null
     */
    public function getContentInLanguage(string $language): ?string
    {
        return $this->content[$language] ?? $this->content['ar'] ?? null;
    }

    /**
     * Register media collections
     *
     * @return void
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('featured')
            ->singleFile()
            ->useDisk('public');

        $this->addMediaCollection('gallery')
            ->useDisk('public');
    }
}

