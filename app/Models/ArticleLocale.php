<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ArticleLocale Model
 *
 * Stores localized content for articles
 *
 * @property int $id
 * @property int $article_id
 * @property string $locale
 * @property string $title
 * @property string $slug
 * @property string|null $description
 * @property string|null $excerpt
 * @property array|null $content
 * @property array|null $seo_metadata
 * @property bool $is_ready
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class ArticleLocale extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'article_locale';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'article_id',
        'locale',
        'title',
        'slug',
        'description',
        'excerpt',
        'content',
        'seo_metadata',
        'is_ready',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'content' => 'array',
            'seo_metadata' => 'array',
            'is_ready' => 'boolean',
        ];
    }

    /**
     * Get the article this locale belongs to
     */
    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }

    /**
     * Get content body from content JSON
     */
    public function getBody(): ?string
    {
        return $this->content['body'] ?? null;
    }

    /**
     * Get table of contents from content JSON
     */
    public function getToc(): ?array
    {
        return $this->content['toc'] ?? null;
    }

    /**
     * Get summary from content JSON
     */
    public function getSummary(): ?string
    {
        return $this->content['summary'] ?? null;
    }

    /**
     * Get the localized SEO title from the metadata payload.
     */
    public function getSeoTitleAttribute(): ?string
    {
        return $this->seo_metadata['title'] ?? null;
    }

    /**
     * Get the localized SEO description from the metadata payload.
     */
    public function getSeoDescriptionAttribute(): ?string
    {
        return $this->seo_metadata['description'] ?? null;
    }

    /**
     * Set content with structure
     */
    public function setContent(?string $body, ?array $toc = null, ?string $summary = null): void
    {
        $this->content = [
            'body' => $body,
            'toc' => $toc,
            'summary' => $summary,
        ];
    }
}
