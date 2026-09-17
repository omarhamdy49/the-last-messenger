<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\Blog\Models\Category;
use App\Domain\Blog\Models\Series;
use App\Domain\Blog\Models\Tag;
use App\Domain\Media\Models\MediaEpisode;
use App\Domain\Shared\Services\PublicImageService;
use App\Domain\Shared\Traits\HasVersions;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Spatie\Translatable\HasTranslations;

/**
 * Article Model
 *
 * @property int $id
 * @property string|null $slug
 * @property string|null $thumb
 * @property int|null $writer_id
 * @property int $read_time
 * @property int $views
 * @property bool $is_featured
 * @property bool $is_pinned
 * @property bool $featured
 * @property int|null $category_id
 * @property string|null $hijri_date
 * @property array|null $title
 * @property array|null $excerpt
 * @property array|null $content
 * @property array|null $seo_title
 * @property array|null $seo_description
 * @property string|null $seo_keywords
 * @property string|null $featured_image
 * @property string|null $og_image
 * @property int $reading_time_minutes
 * @property string|null $status
 * @property array|null $statistics
 * @property array|null $references
 * @property Carbon|null $published_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon|null $deleted_at
 */
class Article extends Model
{
    use HasFactory, HasTranslations, HasVersions, SoftDeletes;

    public const STATUS_PUBLISHED = 'published';

    public const STATUS_DRAFT = 'draft';

    public const STATUS_PENDING = 'pending';

    public const STATUS_SCHEDULED = 'scheduled';

    public const STATUS_ARCHIVED = 'archived';

    /** @var array<string> */
    public array $translatable = ['title', 'excerpt', 'content', 'seo_title', 'seo_description'];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'slug',
        'thumb',
        'writer_id',
        'user_id',
        'read_time',
        'views',
        'is_featured',
        'is_pinned',
        'featured',
        'category_id',
        'hijri_date',
        'title',
        'excerpt',
        'content',
        'seo_title',
        'seo_description',
        'seo_keywords',
        'featured_image',
        'og_image',
        'reading_time_minutes',
        'status',
        'scheduled_at',
        'published_at',
        'statistics',
        'references',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_featured' => 'boolean',
            'is_pinned' => 'boolean',
            'featured' => 'boolean',
            'statistics' => 'array',
            'references' => 'array',
            'published_at' => 'datetime',
        ];
    }

    /**
     * Return the list of translatable fields (mirrors Spatie's $translatable).
     * Satisfies HasLocalizedContent-style assertions in tests.
     *
     * @return array<string>
     */
    public function getTranslatableFields(): array
    {
        return $this->translatable;
    }

    /**
     * Check if the article is published.
     */
    public function isPublished(): bool
    {
        return $this->status === self::STATUS_PUBLISHED;
    }

    /**
     * Get the canonical URL for this article.
     */
    public function getUrl(): string
    {
        if ($this->slug) {
            return url('/articles/'.$this->slug);
        }

        return url('/articles/'.$this->id);
    }

    /**
     * Get the OG image URL (featured_image takes priority over og_image).
     */
    public function getOgImage(): ?string
    {
        return $this->featured_image ?? $this->og_image ?? null;
    }

    /**
     * Allow tests/legacy code to pass user_id; store it as writer_id.
     * This keeps the DB column as writer_id while accepting user_id in fill().
     */
    public function setUserIdAttribute(int $value): void
    {
        $this->attributes['writer_id'] = $value;
    }

    /**
     * Get the writer of the article (primary relation).
     */
    public function writer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'writer_id');
    }

    /**
     * Author accessor — returns the writer User.
     * The SeoService and tests access $article->author.
     */
    public function getAuthorAttribute(): ?User
    {
        return $this->relationLoaded('writer') ? $this->writer : $this->writer()->first();
    }

    /**
     * Get the primary category
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get all categories (many-to-many)
     */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'article_category')
            ->withTimestamps();
    }

    /**
     * Get all localizations
     */
    public function locales(): HasMany
    {
        return $this->hasMany(ArticleLocale::class);
    }

    /**
     * Get all gallery images
     */
    public function gallery(): HasMany
    {
        return $this->hasMany(ArticleGallery::class)->orderBy('order');
    }

    /**
     * Get all media attachments (many-to-many) - for videos/episodes
     */
    public function mediaItems(): BelongsToMany
    {
        return $this->belongsToMany(Media::class, 'articles_media', 'article_id', 'media_id')
            ->withTimestamps()
            ->orderBy('articles_media.created_at', 'desc');
    }

    /**
     * Get only video/episode media items
     */
    /**
     * Media episodes cited by this article, in the editor's chosen order.
     *
     * The article points at existing episodes rather than owning video links,
     * so one lecture has a single canonical record and every article citing it
     * is discoverable from the episode side.
     */
    public function episodes(): BelongsToMany
    {
        return $this->belongsToMany(MediaEpisode::class, 'article_media_episode')
            ->withPivot('position')
            ->withTimestamps()
            ->orderBy('article_media_episode.position');
    }

    public function videoEpisodes(): BelongsToMany
    {
        return $this->mediaItems()->whereIn('type', ['video', 'episode']);
    }

    /**
     * Get thumbnail URL
     */
    public function getThumbUrlAttribute(): string
    {
        return $this->thumb ? asset('storage/'.$this->thumb) : asset('images/default-article-thumb.jpg');
    }

    /**
     * Thumbnail URL sized for its render context ('card' ~640w, 'hero' ~1280w).
     * Falls back to the original file for legacy thumbs without derived copies.
     */
    public function thumbUrl(string $size): ?string
    {
        if (! $this->thumb) {
            return null;
        }

        $derived = PublicImageService::derivedPath($this->thumb, $size);

        return Storage::disk('public')->exists($derived)
            ? asset('storage/'.$derived)
            : asset('storage/'.$this->thumb);
    }

    /**
     * Get all tags (polymorphic many-to-many)
     */
    public function tags(): MorphToMany
    {
        return $this->morphToMany(Tag::class, 'taggable', 'taggables')
            ->withTimestamps();
    }

    /**
     * Increment views count
     */
    public function incrementViews(): void
    {
        $this->increment('views');
    }

    /**
     * Get localization record for specific language.
     * Named getArticleLocale to avoid conflict with Spatie HasTranslations::getLocale().
     */
    public function getArticleLocale(string $locale): ?ArticleLocale
    {
        return $this->locales()->where('locale', $locale)->first();
    }

    /**
     * Check if article is ready in specific language
     */
    public function isReadyInLocale(string $locale): bool
    {
        $localeModel = $this->getArticleLocale($locale);

        return $localeModel && $localeModel->is_ready;
    }

    /**
     * Get all series this article belongs to (many-to-many)
     */
    public function series(): BelongsToMany
    {
        return $this->belongsToMany(Series::class, 'article_series')
            ->withPivot('position')
            ->withTimestamps()
            ->orderBy('article_series.position');
    }
}
