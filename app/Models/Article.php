<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Spatie\Translatable\HasTranslations;

/**
 * Article Model
 *
 * Represents Seerah (biography) articles and blog posts in The Last Messenger platform.
 * Supports multi-language content (AR, EN, FR, TR, UR) and comprehensive content management.
 *
 * @property int $id
 * @property string $slug Unique URL-friendly identifier
 * @property int $user_id Article author ID
 * @property int|null $reviewer_id Article reviewer ID
 * @property int|null $category_id Category ID
 * @property int|null $series_id Series ID for linked articles
 * @property array $title Multi-language title (JSON: {ar, en, fr, tr, ur})
 * @property array|null $excerpt Multi-language excerpt (JSON)
 * @property array $content Multi-language full content (JSON)
 * @property string|null $featured_image Featured image path
 * @property array|null $seo_title Multi-language SEO title (JSON)
 * @property array|null $seo_description Multi-language SEO description (JSON)
 * @property string|null $seo_keywords Comma-separated SEO keywords
 * @property string|null $og_image Open Graph image path
 * @property int $reading_time_minutes Estimated reading time
 * @property int $views_count Total view count
 * @property int $favorites_count Total favorites count
 * @property int $order Display order (for series)
 * @property bool $featured Featured article flag
 * @property bool $is_pinned Pinned to top flag
 * @property string $status Article status (draft|under_review|published|archived|rejected)
 * @property \Carbon\Carbon|null $published_at Publication timestamp
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at Soft delete timestamp
 *
 * @property-read User $author Article author
 * @property-read User|null $reviewer Article reviewer
 * @property-read Category|null $category Article category
 * @property-read Series|null $series Article series
 * @property-read \Illuminate\Database\Eloquent\Collection|Tag[] $tags Article tags
 *
 * @method static Builder published() Get only published articles
 * @method static Builder draft() Get only draft articles
 * @method static Builder underReview() Get only articles under review
 * @method static Builder featured() Get only featured articles
 * @method static Builder pinned() Get only pinned articles
 * @method static Builder search(string $query, string $locale = 'ar') Search articles
 * @method static Builder byCategory(int $categoryId) Filter by category
 * @method static Builder bySeries(int $seriesId) Filter by series
 * @method static Builder byAuthor(int $userId) Filter by author
 * @method static Builder popular() Order by views count
 * @method static Builder recent() Order by published date
 */
class Article extends Model
{
    use HasFactory;
    use HasTranslations;
    use SoftDeletes;

    /**
     * Article status constants
     */
    public const STATUS_DRAFT = 'draft';
    public const STATUS_UNDER_REVIEW = 'under_review';
    public const STATUS_PUBLISHED = 'published';
    public const STATUS_ARCHIVED = 'archived';
    public const STATUS_REJECTED = 'rejected';

    /**
     * Available statuses
     */
    public const STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_UNDER_REVIEW,
        self::STATUS_PUBLISHED,
        self::STATUS_ARCHIVED,
        self::STATUS_REJECTED,
    ];

    /**
     * Supported languages for content
     */
    public const SUPPORTED_LANGUAGES = ['ar', 'en', 'fr', 'tr', 'ur'];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'slug',
        'user_id',
        'reviewer_id',
        'category_id',
        'series_id',
        'title',
        'excerpt',
        'content',
        'featured_image',
        'seo_title',
        'seo_description',
        'seo_keywords',
        'og_image',
        'reading_time_minutes',
        'order',
        'featured',
        'is_pinned',
        'status',
        'published_at',
    ];

    /**
     * Translatable attributes using Spatie Laravel Translatable
     *
     * @var array<string>
     */
    public $translatable = [
        'title',
        'excerpt',
        'content',
        'seo_title',
        'seo_description',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
            'featured' => 'boolean',
            'is_pinned' => 'boolean',
            'reading_time_minutes' => 'integer',
            'views_count' => 'integer',
            'favorites_count' => 'integer',
            'order' => 'integer',
        ];
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [];

    /**
     * Boot the model and register event listeners
     */
    protected static function boot(): void
    {
        parent::boot();

        // Auto-calculate reading time on content change
        static::saving(function (Article $article) {
            if ($article->isDirty('content')) {
                $article->reading_time_minutes = $article->calculateReadingTime();
            }
        });
    }

    // ========================================================================
    // RELATIONSHIPS
    // ========================================================================

    /**
     * Get the article author
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the article reviewer
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    /**
     * Get the article category
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get the article series
     */
    public function series(): BelongsTo
    {
        return $this->belongsTo(Series::class);
    }

    /**
     * Get all tags for this article (polymorphic relationship)
     */
    public function tags(): MorphToMany
    {
        return $this->morphToMany(Tag::class, 'taggable');
    }

    // ========================================================================
    // QUERY SCOPES
    // ========================================================================

    /**
     * Scope: Get only published articles
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PUBLISHED)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    /**
     * Scope: Get only draft articles
     */
    public function scopeDraft(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_DRAFT);
    }

    /**
     * Scope: Get only articles under review
     */
    public function scopeUnderReview(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_UNDER_REVIEW);
    }

    /**
     * Scope: Get only archived articles
     */
    public function scopeArchived(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ARCHIVED);
    }

    /**
     * Scope: Get only rejected articles
     */
    public function scopeRejected(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_REJECTED);
    }

    /**
     * Scope: Get only featured articles
     */
    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('featured', true);
    }

    /**
     * Scope: Get only pinned articles
     */
    public function scopePinned(Builder $query): Builder
    {
        return $query->where('is_pinned', true);
    }

    /**
     * Scope: Search articles by title and excerpt
     *
     * Uses MySQL full-text search on generated columns for better performance
     *
     * @param Builder $query
     * @param string $searchQuery Search term
     * @param string $locale Language to search in (default: 'ar')
     */
    public function scopeSearch(Builder $query, string $searchQuery, string $locale = 'ar'): Builder
    {
        if (empty($searchQuery)) {
            return $query;
        }

        // Use MySQL full-text search if available
        if (config('database.default') === 'mysql' && $locale === 'ar') {
            return $query->whereRaw(
                "MATCH(title_searchable, excerpt_searchable) AGAINST(? IN BOOLEAN MODE)",
                [$searchQuery . '*']
            );
        }

        // Fallback to JSON search for other locales or databases
        $searchTerm = '%' . $searchQuery . '%';

        return $query->where(function (Builder $q) use ($searchTerm, $locale) {
            $q->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(title, '$.{$locale}')) LIKE ?", [$searchTerm])
                ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(excerpt, '$.{$locale}')) LIKE ?", [$searchTerm])
                ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(content, '$.{$locale}')) LIKE ?", [$searchTerm]);
        });
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
     * Scope: Filter by author
     */
    public function scopeByAuthor(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope: Order by popularity (views count)
     */
    public function scopePopular(Builder $query): Builder
    {
        return $query->orderBy('views_count', 'desc');
    }

    /**
     * Scope: Order by recent publication date
     */
    public function scopeRecent(Builder $query): Builder
    {
        return $query->orderBy('published_at', 'desc');
    }

    // ========================================================================
    // HELPER METHODS - WORKFLOW
    // ========================================================================

    /**
     * Publish the article
     *
     * Sets status to published and sets publication timestamp if not already set
     */
    public function publish(): bool
    {
        $this->status = self::STATUS_PUBLISHED;

        if (is_null($this->published_at)) {
            $this->published_at = now();
        }

        return $this->save();
    }

    /**
     * Send article to review
     *
     * Changes status from draft to under_review
     */
    public function sendToReview(): bool
    {
        if ($this->status !== self::STATUS_DRAFT) {
            return false;
        }

        $this->status = self::STATUS_UNDER_REVIEW;

        return $this->save();
    }

    /**
     * Approve the article (by reviewer)
     *
     * @param User $reviewer The user approving the article
     */
    public function approve(User $reviewer): bool
    {
        if ($this->status !== self::STATUS_UNDER_REVIEW) {
            return false;
        }

        $this->reviewer_id = $reviewer->id;
        $this->status = self::STATUS_PUBLISHED;

        if (is_null($this->published_at)) {
            $this->published_at = now();
        }

        return $this->save();
    }

    /**
     * Reject the article (by reviewer)
     *
     * @param User $reviewer The user rejecting the article
     */
    public function reject(User $reviewer): bool
    {
        if ($this->status !== self::STATUS_UNDER_REVIEW) {
            return false;
        }

        $this->reviewer_id = $reviewer->id;
        $this->status = self::STATUS_REJECTED;

        return $this->save();
    }

    /**
     * Archive the article
     */
    public function archive(): bool
    {
        $this->status = self::STATUS_ARCHIVED;

        return $this->save();
    }

    /**
     * Revert to draft
     */
    public function revertToDraft(): bool
    {
        $this->status = self::STATUS_DRAFT;
        $this->reviewer_id = null;

        return $this->save();
    }

    // ========================================================================
    // HELPER METHODS - COUNTERS
    // ========================================================================

    /**
     * Increment view count
     *
     * Uses direct DB update to avoid loading the entire model
     */
    public function incrementViewCount(): void
    {
        DB::table('articles')
            ->where('id', $this->id)
            ->increment('views_count');

        // Update local instance
        $this->views_count++;
    }

    /**
     * Increment favorites count
     */
    public function incrementFavoritesCount(): bool
    {
        return $this->increment('favorites_count');
    }

    /**
     * Decrement favorites count
     */
    public function decrementFavoritesCount(): bool
    {
        return $this->decrement('favorites_count');
    }

    // ========================================================================
    // HELPER METHODS - CONTENT
    // ========================================================================

    /**
     * Calculate reading time based on content
     *
     * Assumes average reading speed of 200 words per minute
     *
     * @return int Reading time in minutes
     */
    public function calculateReadingTime(): int
    {
        $wordCount = 0;

        // Count words across all languages
        foreach (self::SUPPORTED_LANGUAGES as $locale) {
            $content = $this->getTranslation('content', $locale, false);

            if (!empty($content)) {
                // Strip HTML tags and count words
                $text = strip_tags($content);
                $wordCount += str_word_count($text);
            }
        }

        // Use the longest translation for reading time
        // Average reading speed: 200 words per minute
        $minutes = ceil($wordCount / 200);

        return max(1, $minutes); // Minimum 1 minute
    }

    /**
     * Get excerpt with fallback to content
     *
     * @param string $locale Language code
     * @param int $length Maximum length in characters
     * @return string
     */
    public function getExcerptOrContent(string $locale = 'ar', int $length = 200): string
    {
        $excerpt = $this->getTranslation('excerpt', $locale, false);

        if (!empty($excerpt)) {
            return $excerpt;
        }

        // Fallback to truncated content
        $content = $this->getTranslation('content', $locale, false);
        $text = strip_tags($content ?? '');

        return mb_substr($text, 0, $length) . (mb_strlen($text) > $length ? '...' : '');
    }

    // ========================================================================
    // HELPER METHODS - STATUS & DISPLAY
    // ========================================================================

    /**
     * Check if article is published
     */
    public function isPublished(): bool
    {
        return $this->status === self::STATUS_PUBLISHED
            && !is_null($this->published_at)
            && $this->published_at->lte(now());
    }

    /**
     * Check if article is draft
     */
    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    /**
     * Check if article is under review
     */
    public function isUnderReview(): bool
    {
        return $this->status === self::STATUS_UNDER_REVIEW;
    }

    /**
     * Check if article is archived
     */
    public function isArchived(): bool
    {
        return $this->status === self::STATUS_ARCHIVED;
    }

    /**
     * Check if article is rejected
     */
    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    /**
     * Get status badge color for UI display
     *
     * Returns Tailwind CSS color class
     *
     * @return string Color class (e.g., 'green', 'yellow', 'red')
     */
    public function getStatusBadgeColor(): string
    {
        return match ($this->status) {
            self::STATUS_PUBLISHED => 'green',
            self::STATUS_DRAFT => 'gray',
            self::STATUS_UNDER_REVIEW => 'yellow',
            self::STATUS_ARCHIVED => 'blue',
            self::STATUS_REJECTED => 'red',
            default => 'gray',
        };
    }

    /**
     * Get human-readable status label
     *
     * @return string
     */
    public function getStatusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_PUBLISHED => 'Published',
            self::STATUS_DRAFT => 'Draft',
            self::STATUS_UNDER_REVIEW => 'Under Review',
            self::STATUS_ARCHIVED => 'Archived',
            self::STATUS_REJECTED => 'Rejected',
            default => ucfirst($this->status),
        };
    }

    /**
     * Get the article's URL
     *
     * @return string
     */
    public function getUrl(): string
    {
        return route('articles.show', $this->slug);
    }

    /**
     * Get SEO title with fallback to regular title
     *
     * @param string $locale
     * @return string
     */
    public function getSeoTitle(string $locale = 'ar'): string
    {
        $seoTitle = $this->getTranslation('seo_title', $locale, false);

        return $seoTitle ?: $this->getTranslation('title', $locale);
    }

    /**
     * Get SEO description with fallback to excerpt
     *
     * @param string $locale
     * @return string
     */
    public function getSeoDescription(string $locale = 'ar'): string
    {
        $seoDescription = $this->getTranslation('seo_description', $locale, false);

        return $seoDescription ?: $this->getExcerptOrContent($locale, 160);
    }

    /**
     * Get Open Graph image with fallback to featured image
     *
     * @return string|null
     */
    public function getOgImage(): ?string
    {
        return $this->og_image ?: $this->featured_image;
    }
}
