<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\Blog\Models\Category;
use App\Domain\Blog\Models\Tag;
use App\Domain\Shared\Traits\HasVersions;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Article Model
 *
 * @property int $id
 * @property string|null $thumb
 * @property int|null $writer_id
 * @property int $read_time
 * @property int $views
 * @property bool $is_featured
 * @property bool $is_pinned
 * @property int|null $category_id
 * @property string|null $hijri_date
 * @property array|null $statistics
 * @property array|null $references
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 */
class Article extends Model
{
    use HasFactory, SoftDeletes, HasVersions;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'thumb',
        'writer_id',
        'read_time',
        'views',
        'is_featured',
        'is_pinned',
        'category_id',
        'hijri_date',
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
            'statistics' => 'array',
            'references' => 'array',
        ];
    }

    /**
     * Get the writer of the article
     */
    public function writer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'writer_id');
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
            ->orderBy('created_at', 'desc');
    }

    /**
     * Get only video/episode media items
     */
    public function videoEpisodes(): BelongsToMany
    {
        return $this->mediaItems()->whereIn('type', ['video', 'episode']);
    }

    /**
     * Get thumbnail URL
     */
    public function getThumbUrlAttribute(): string
    {
        return $this->thumb ? asset('storage/' . $this->thumb) : asset('images/default-article-thumb.jpg');
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
     * Get localization for specific language
     */
    public function getLocale(string $locale): ?ArticleLocale
    {
        return $this->locales()->where('locale', $locale)->first();
    }

    /**
     * Check if article is ready in specific language
     */
    public function isReadyInLocale(string $locale): bool
    {
        $localeModel = $this->getLocale($locale);
        return $localeModel && $localeModel->is_ready;
    }
}
