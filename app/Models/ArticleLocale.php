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
     * Get content body from content JSON.
     *
     * If body is a blocks array, renders it to HTML for public API consumers.
     * If body is a string (legacy), returns as-is.
     */
    public function getBody(): ?string
    {
        $body = $this->content['body'] ?? null;

        if ($body === null) {
            return null;
        }

        if (is_array($body)) {
            return $this->renderBlocksToHtml($body);
        }

        return $body;
    }

    /**
     * Get content blocks array for the admin form.
     *
     * If body is a blocks array, returns it directly.
     * If body is a legacy HTML string, wraps it in a single WYSIWYG block.
     */
    public function getBlocks(): array
    {
        $body = $this->content['body'] ?? null;

        if ($body === null) {
            return [];
        }

        if (is_array($body)) {
            return $body;
        }

        // Legacy HTML string — wrap in a single WYSIWYG block
        return [
            ['type' => 'wysiwyg', 'content' => $body],
        ];
    }

    /**
     * Render content blocks array to HTML for public API.
     */
    private function renderBlocksToHtml(array $blocks): string
    {
        $html = '';

        foreach ($blocks as $block) {
            $type = $block['type'] ?? '';

            switch ($type) {
                case 'wysiwyg':
                    $html .= $block['content'] ?? '';
                    break;

                case 'verse':
                    $surahName = $block['surah_name'] ?? '';
                    $html .= '<blockquote class="quran-quote">';
                    foreach ($block['verses'] ?? [] as $verse) {
                        $html .= '<p class="verse-text">'.e($verse['text'] ?? '').' <span class="verse-number">('.e($verse['number'] ?? '').')</span></p>';
                    }
                    $html .= '<footer>'.e($surahName).'</footer>';
                    $html .= '</blockquote>';
                    break;

                case 'hadith':
                    $html .= '<blockquote class="hadith-quote">';
                    $html .= '<p>'.e($block['text'] ?? '').'</p>';
                    $collection = $block['collection'] ?? '';
                    $number = $block['number'] ?? '';
                    $grade = $block['grade'] ?? '';
                    $html .= '<footer>'.e($collection).' - '.e($number).' ('.e($grade).')</footer>';
                    $html .= '</blockquote>';
                    break;

                case 'sahabi':
                    $html .= '<div class="sahabi-card">';
                    $html .= '<strong>'.e($block['name'] ?? '').'</strong>';
                    if (! empty($block['kunya'])) {
                        $html .= ' <span>('.e($block['kunya']).')</span>';
                    }
                    $html .= '</div>';
                    break;

                case 'seerah':
                    $html .= '<div class="seerah-card">';
                    $html .= '<strong>'.e($block['title'] ?? '').'</strong>';
                    if (! empty($block['hijri_year'])) {
                        $html .= ' <span>'.e($block['hijri_year']).' هـ</span>';
                    }
                    $html .= '</div>';
                    break;
            }
        }

        return $html;
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
     * Set content with structure.
     *
     * @param  string|array|null  $body  HTML string or blocks array.
     */
    public function setContent(string|array|null $body, ?array $toc = null, ?string $summary = null): void
    {
        $this->content = [
            'body' => $body,
            'toc' => $toc,
            'summary' => $summary,
        ];
    }
}
