<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
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
 * @property array|null $meta_description Multi-language meta descriptions (JSON)
 * @property array|null $keywords Array of SEO keywords (JSON array)
 * @property array|null $share_text Multi-language social share text (JSON)
 * @property string|null $social_image Social media preview image path
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
 * @property-read \Illuminate\Database\Eloquent\Collection|ArticleGallery[] $gallery Gallery images
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
     * Difficulty level constants for educational content
     */
    public const DIFFICULTY_BEGINNER = 'beginner';
    public const DIFFICULTY_INTERMEDIATE = 'intermediate';
    public const DIFFICULTY_ADVANCED = 'advanced';

    /**
     * Available difficulty levels
     */
    public const DIFFICULTY_LEVELS = [
        self::DIFFICULTY_BEGINNER,
        self::DIFFICULTY_INTERMEDIATE,
        self::DIFFICULTY_ADVANCED,
    ];

    /**
     * Target audience constants
     */
    public const AUDIENCE_CHILDREN = 'children';
    public const AUDIENCE_YOUTH = 'youth';
    public const AUDIENCE_ADULTS = 'adults';
    public const AUDIENCE_SCHOLARS = 'scholars';

    /**
     * Available target audiences
     */
    public const TARGET_AUDIENCES = [
        self::AUDIENCE_CHILDREN,
        self::AUDIENCE_YOUTH,
        self::AUDIENCE_ADULTS,
        self::AUDIENCE_SCHOLARS,
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'slug',
        'user_id',
        'author_id',
        'co_authors',
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
        'meta_description',
        'keywords',
        'share_text',
        'social_image',
        'reading_time_minutes',
        'reading_time_override',
        'last_viewed_at',
        'comments_enabled',
        'comment_moderation',
        'show_toc',
        'references',
        'has_content_warning',
        'disclaimer_text',
        'created_by',
        'last_edited_by',
        'order',
        'featured',
        'is_pinned',
        'status',
        'published_at',
        // Seerah-specific fields
        'hijri_date',
        'gregorian_date',
        'event_start_date',
        'event_end_date',
        'event_locations',
        'primary_location',
        'difficulty_level',
        'target_audience',
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
        'meta_description',
        'share_text',
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
            'last_viewed_at' => 'datetime',
            'featured' => 'boolean',
            'is_pinned' => 'boolean',
            'comments_enabled' => 'boolean',
            'show_toc' => 'boolean',
            'has_content_warning' => 'boolean',
            'reading_time_minutes' => 'integer',
            'reading_time_override' => 'integer',
            'views_count' => 'integer',
            'favorites_count' => 'integer',
            'order' => 'integer',
            'co_authors' => 'array',
            'references' => 'array',
            // Seerah-specific date casts
            'gregorian_date' => 'date',
            'event_start_date' => 'date',
            'event_end_date' => 'date',
            // JSON cast for event locations
            'event_locations' => 'array',
            // SEO and social media metadata (JSON for multi-language support)
            'meta_description' => 'array',
            'keywords' => 'array',
            'share_text' => 'array',
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
     * Get the user who created this article
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last edited this article
     */
    public function lastEditedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'last_edited_by');
    }

    /**
     * Get all gallery images for this article
     */
    public function gallery(): HasMany
    {
        return $this->hasMany(ArticleGallery::class)
            ->orderBy('order');
    }

    /**
     * Get all tags for this article (polymorphic relationship)
     */
    public function tags(): MorphToMany
    {
        return $this->morphToMany(Tag::class, 'taggable');
    }


    /**
     * Get related articles (articles this article links to)
     *
     * Returns a many-to-many relationship with other articles,
     * ordered by the 'order' column in the pivot table.
     */
    public function relatedArticles(): BelongsToMany
    {
        return $this->belongsToMany(
            Article::class,
            'article_related',
            'article_id',
            'related_article_id'
        )
            ->withPivot('order')
            ->withTimestamps()
            ->orderByPivot('order', 'asc');
    }

    /**
     * Get articles that link to this article (inverse relationship)
     *
     * Returns articles where this article is listed as a related article.
     * Useful for bidirectional navigation and finding all references to this article.
     */
    public function relatedFrom(): BelongsToMany
    {
        return $this->belongsToMany(
            Article::class,
            'article_related',
            'related_article_id',
            'article_id'
        )
            ->withPivot('order')
            ->withTimestamps()
            ->orderByPivot('order', 'asc');
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
     * Increment view count and update last viewed timestamp
     *
     * Alias method for incrementViewCount with additional timestamp update
     */
    public function incrementViews(): void
    {
        DB::table('articles')
            ->where('id', $this->id)
            ->update([
                'views_count' => DB::raw('views_count + 1'),
                'last_viewed_at' => now(),
            ]);

        // Update local instance
        $this->views_count++;
        $this->last_viewed_at = now();
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

    // ========================================================================
    // ACCESSORS - SEO & SOCIAL MEDIA
    // ========================================================================

    /**
     * Get full URL for Open Graph image
     *
     * Returns absolute URL for og_image with fallback to featured_image
     *
     * @return string|null
     */
    public function getOgImageUrlAttribute(): ?string
    {
        $image = $this->og_image ?: $this->featured_image;

        if (!$image) {
            return null;
        }

        // If already absolute URL, return as is
        if (filter_var($image, FILTER_VALIDATE_URL)) {
            return $image;
        }

        // Build absolute URL
        return url('storage/' . $image);
    }

    /**
     * Get full URL for social media image
     *
     * Returns absolute URL for social_image with fallback to og_image or featured_image
     *
     * @return string|null
     */
    public function getSocialImageUrlAttribute(): ?string
    {
        $image = $this->social_image ?: $this->og_image ?: $this->featured_image;

        if (!$image) {
            return null;
        }

        // If already absolute URL, return as is
        if (filter_var($image, FILTER_VALIDATE_URL)) {
            return $image;
        }

        // Build absolute URL
        return url('storage/' . $image);
    }

    /**
     * Get meta description with fallback hierarchy
     *
     * Fallback order: meta_description -> seo_description -> excerpt
     *
     * @param string $locale Language code
     * @return string
     */
    public function getMetaDescription(string $locale = 'ar'): string
    {
        // First try meta_description
        $metaDescription = $this->getTranslation('meta_description', $locale, false);
        if (!empty($metaDescription)) {
            return $metaDescription;
        }

        // Then try seo_description
        $seoDescription = $this->getTranslation('seo_description', $locale, false);
        if (!empty($seoDescription)) {
            return $seoDescription;
        }

        // Fallback to excerpt
        return $this->getExcerptOrContent($locale, 160);
    }

    /**
     * Get share text with fallback to title
     *
     * @param string $locale Language code
     * @return string
     */
    public function getShareText(string $locale = 'ar'): string
    {
        $shareText = $this->getTranslation('share_text', $locale, false);

        return $shareText ?: $this->getTranslation('title', $locale);
    }

    /**
     * Get keywords as array (handles both JSON array and comma-separated string)
     *
     * @return array
     */
    public function getKeywordsArray(): array
    {
        if (is_array($this->keywords)) {
            return $this->keywords;
        }

        if (empty($this->keywords)) {
            return [];
        }

        return array_map('trim', explode(',', (string) $this->keywords));
    }

    /**
     * Get social media image with fallback chain
     *
     * Fallback order: social_image -> og_image -> featured_image
     *
     * @return string|null
     */
    public function getSocialImage(): ?string
    {
        return $this->social_image ?: $this->og_image ?: $this->featured_image;
    }

    // ========================================================================
    // ACCESSORS - READING TIME
    // ========================================================================

    /**
     * Get reading time with override support
     *
     * Returns reading_time_override if set, otherwise returns auto-calculated reading_time_minutes
     *
     * @return int Reading time in minutes
     */
    public function getReadingTimeAttribute(): int
    {
        // If manual override is set, use it
        if (!is_null($this->reading_time_override)) {
            return $this->reading_time_override;
        }

        // Otherwise return the auto-calculated reading time
        return $this->attributes['reading_time_minutes'] ?? 0;
    }
}
