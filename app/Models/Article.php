<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\Blog\Models\Category;
use App\Domain\Blog\Models\Tag;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * Article Model with JSON Multi-Language Support
 *
 * @property int $id
 * @property string $slug Unified slug (auto-generated from English title)
 * @property string|null $thumb
 * @property array $title {ar, en, fr, tr, ur}
 * @property array|null $description {ar, en, fr, tr, ur} - WYSIWYG
 * @property array|null $excerpt {ar, en, fr, tr, ur} - Plain text
 * @property array $content {ar: {body, toc, summarize_points, summary}, en: {...}}
 * @property string|null $publish_date Gregorian calendar
 * @property string|null $publish_date_hijri Hijri calendar
 * @property \Carbon\Carbon|null $published_at
 * @property int|null $category_id
 * @property int|null $writer
 * @property int $views
 * @property int $read_time
 * @property array|null $references
 * @property array|null $statistics
 * @property string $status
 * @property bool $is_featured
 * @property bool $is_pinned
 * @property array|null $seo_metadata {ar: {title, description, keywords, og_*}, en: {...}}
 */
class Article extends Model
{
    use HasFactory;
    use SoftDeletes;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_UNDER_REVIEW = 'under_review';
    public const STATUS_PUBLISHED = 'published';
    public const STATUS_ARCHIVED = 'archived';
    public const STATUS_REJECTED = 'rejected';

    public const SUPPORTED_LANGUAGES = ['ar', 'en', 'fr', 'tr', 'ur'];

    protected $fillable = [
        'slug',
        'thumb',
        'title',
        'description',
        'excerpt',
        'content',
        'publish_date',
        'publish_date_hijri',
        'published_at',
        'category_id',
        'writer',
        'views',
        'read_time',
        'references',
        'statistics',
        'status',
        'is_featured',
        'is_pinned',
        'seo_metadata',
    ];

    protected function casts(): array
    {
        return [
            'title' => 'array',
            'description' => 'array',
            'excerpt' => 'array',
            'content' => 'array',
            'references' => 'array',
            'statistics' => 'array',
            'seo_metadata' => 'array',
            'is_featured' => 'boolean',
            'is_pinned' => 'boolean',
            'views' => 'integer',
            'read_time' => 'integer',
            'publish_date' => 'date',
            'published_at' => 'datetime',
        ];
    }

    /**
     * Boot model events
     */
    protected static function boot(): void
    {
        parent::boot();

        // Auto-generate slug from English title
        static::creating(function (Article $article) {
            if (empty($article->slug) && isset($article->title['en'])) {
                $article->slug = Str::slug($article->title['en']);
            }
        });

        // Update searchable metadata after save
        static::saved(function (Article $article) {
            $article->updateSearchableMetadata();
        });
    }

    // ========================================================================
    // RELATIONSHIPS
    // ========================================================================

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'article_category');
    }

    public function writerUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'writer');
    }

    public function gallery(): HasMany
    {
        return $this->hasMany(ArticleGallery::class)->orderBy('order');
    }

    public function tags(): MorphToMany
    {
        return $this->morphToMany(Tag::class, 'taggable');
    }

    /**
     * Get searchable metadata for all locales
     */
    public function metadata(): HasMany
    {
        return $this->hasMany(ArticleMetadata::class);
    }

    // ========================================================================
    // SCOPES
    // ========================================================================

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PUBLISHED)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    public function scopeDraft(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_DRAFT);
    }

    public function scopeUnderReview(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_UNDER_REVIEW);
    }

    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    public function scopePinned(Builder $query): Builder
    {
        return $query->where('is_pinned', true);
    }

    /**
     * Search articles using article_metadata table for performance
     */
    public function scopeSearch(Builder $query, string $searchQuery, string $locale = 'ar'): Builder
    {
        if (empty($searchQuery)) {
            return $query;
        }

        return $query->whereHas('metadata', function (Builder $q) use ($searchQuery, $locale) {
            $q->where('locale', $locale)
                ->whereRaw('MATCH(searchable_title, searchable_excerpt) AGAINST(? IN BOOLEAN MODE)', [$searchQuery . '*']);
        });
    }

    public function scopeByCategory(Builder $query, int $categoryId): Builder
    {
        return $query->where('category_id', $categoryId);
    }

    public function scopePopular(Builder $query): Builder
    {
        return $query->orderBy('views', 'desc');
    }

    public function scopeRecent(Builder $query): Builder
    {
        return $query->orderBy('published_at', 'desc');
    }

    // ========================================================================
    // HELPER METHODS
    // ========================================================================

    /**
     * Get localized field
     */
    public function getLocalized(string $field, ?string $locale = null): mixed
    {
        $locale = $locale ?? app()->getLocale();
        $value = $this->$field;

        if (is_array($value) && isset($value[$locale])) {
            return $value[$locale];
        }

        // Fallback to Arabic
        return is_array($value) && isset($value['ar']) ? $value['ar'] : null;
    }

    /**
     * Get title in specific language
     */
    public function getTitle(?string $locale = null): ?string
    {
        return $this->getLocalized('title', $locale);
    }

    /**
     * Get description in specific language
     */
    public function getDescription(?string $locale = null): ?string
    {
        return $this->getLocalized('description', $locale);
    }

    /**
     * Get excerpt in specific language
     */
    public function getExcerpt(?string $locale = null): ?string
    {
        return $this->getLocalized('excerpt', $locale);
    }

    /**
     * Get content body for specific language
     */
    public function getContentBody(?string $locale = null): ?string
    {
        $locale = $locale ?? app()->getLocale();
        $content = $this->content[$locale] ?? $this->content['ar'] ?? null;

        if (is_array($content) && isset($content['body'])) {
            return $content['body'];
        }

        return is_string($content) ? $content : null;
    }

    /**
     * Get content TOC for specific language
     */
    public function getContentToc(?string $locale = null): array
    {
        $locale = $locale ?? app()->getLocale();
        $content = $this->content[$locale] ?? $this->content['ar'] ?? [];

        return is_array($content) && isset($content['toc']) ? $content['toc'] : [];
    }

    /**
     * Get content summarize points for specific language
     */
    public function getContentSummarizePoints(?string $locale = null): array
    {
        $locale = $locale ?? app()->getLocale();
        $content = $this->content[$locale] ?? $this->content['ar'] ?? [];

        return is_array($content) && isset($content['summarize_points']) ? $content['summarize_points'] : [];
    }

    /**
     * Get content summary for specific language
     */
    public function getContentSummary(?string $locale = null): ?string
    {
        $locale = $locale ?? app()->getLocale();
        $content = $this->content[$locale] ?? $this->content['ar'] ?? null;

        return is_array($content) && isset($content['summary']) ? $content['summary'] : null;
    }

    /**
     * Get SEO metadata for specific language
     */
    public function getSeoMetadata(?string $locale = null): array
    {
        $locale = $locale ?? app()->getLocale();
        $seo = $this->seo_metadata[$locale] ?? $this->seo_metadata['ar'] ?? [];

        return is_array($seo) ? $seo : [];
    }

    /**
     * Update searchable metadata for all languages
     */
    public function updateSearchableMetadata(): void
    {
        foreach (self::SUPPORTED_LANGUAGES as $locale) {
            $title = $this->title[$locale] ?? '';
            $excerpt = $this->excerpt[$locale] ?? '';

            // Strip HTML from title and excerpt for search
            $searchableTitle = strip_tags($title);
            $searchableExcerpt = strip_tags($excerpt);

            ArticleMetadata::updateOrCreate(
                ['article_id' => $this->id, 'locale' => $locale],
                [
                    'searchable_title' => $searchableTitle,
                    'searchable_excerpt' => $searchableExcerpt,
                ]
            );
        }
    }

    /**
     * Check if article is published
     */
    public function isPublished(): bool
    {
        return $this->status === self::STATUS_PUBLISHED
            && $this->published_at !== null
            && $this->published_at->isPast();
    }

    /**
     * Publish article
     */
    public function publish(): bool
    {
        $this->status = self::STATUS_PUBLISHED;
        if (!$this->published_at) {
            $this->published_at = now();
        }
        return $this->save();
    }

    /**
     * Send to review
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
     * Increment views
     */
    public function incrementViews(): void
    {
        $this->increment('views');
    }

    /**
     * Get status badge color
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
}
