<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Route;

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
     * Render content blocks array to HTML for the public reader / API.
     *
     * Module blocks (verse / hadith / sahabi / seerah) render as premium,
     * self-contained cards whose footer links through to the canonical detail
     * page for that entity. Link building is guarded by Route::has() and a
     * present id/slug, so legacy blocks without one degrade to a plain label.
     */
    private function renderBlocksToHtml(array $blocks): string
    {
        $html = '';

        foreach ($blocks as $block) {
            $html .= match ($block['type'] ?? '') {
                'wysiwyg' => $block['content'] ?? '',
                'verse' => $this->renderVerseBlock($block),
                'hadith' => $this->renderHadithBlock($block),
                'sahabi' => $this->renderSahabiBlock($block),
                'seerah' => $this->renderSeerahBlock($block),
                default => '',
            };
        }

        return $html;
    }

    /**
     * Build a URL to a public detail route, or null when the route or id/slug
     * is unavailable (so the converter never throws on legacy content).
     */
    private function moduleUrl(string $routeName, int|string|null $param, ?string $fragment = null): ?string
    {
        if ($param === null || $param === '' || ! Route::has($routeName)) {
            return null;
        }

        return route($routeName, $param).($fragment !== null ? '#'.$fragment : '');
    }

    private function embedLabel(string $icon, string $label): string
    {
        return '<span class="module-embed__label"><i data-lucide="'.$icon.'"></i>'.e($label).'</span>';
    }

    private function embedLink(?string $href, string $icon, string $text): string
    {
        if ($href === null) {
            return '<span class="module-embed__source">'.e($text).'</span>';
        }

        return '<a class="module-embed__link" href="'.e($href).'"><i data-lucide="'.$icon.'"></i><span>'.e($text).'</span></a>';
    }

    private function renderVerseBlock(array $block): string
    {
        $verses = $block['verses'] ?? [];
        $surahName = (string) ($block['surah_name'] ?? '');
        $numbers = array_values(array_filter(
            array_map(fn ($v) => $v['number'] ?? null, $verses),
            fn ($n) => $n !== null && $n !== ''
        ));
        $range = match (true) {
            $numbers === [] => '',
            count($numbers) === 1 => __('articles.embed.verse_one', ['n' => $numbers[0]]),
            default => __('articles.embed.verse_range', ['from' => $numbers[0], 'to' => end($numbers)]),
        };
        $href = $this->moduleUrl('public.quran.surah', $block['surah_id'] ?? null, $numbers !== [] ? 'verse-'.$numbers[0] : null);
        $source = trim($surahName.($range !== '' ? ' — '.$range : ''));

        $body = '';
        foreach ($verses as $verse) {
            $body .= '<p>'.e($verse['text'] ?? '').' <span class="verse-number">'.e(arabic_digits($verse['number'] ?? '')).'</span></p>';
        }

        return '<aside class="module-embed module-embed--verse" dir="rtl">'
            .$this->embedLabel('book-open', __('articles.embed.quran'))
            .'<div class="module-embed__body module-embed__arabic">'.$body.'</div>'
            .$this->embedLink($href, 'external-link', $source !== '' ? $source : __('articles.embed.view_in_quran'))
            .'</aside>';
    }

    private function renderHadithBlock(array $block): string
    {
        $collection = (string) ($block['collection'] ?? '');
        $number = (string) ($block['number'] ?? '');
        $grade = (string) ($block['grade'] ?? '');
        $href = $this->moduleUrl('public.hadith.show', $block['hadith_id'] ?? null);
        $source = trim($collection.($number !== '' ? ' #'.$number : ''));
        $gradeBadge = $grade !== ''
            ? '<span class="module-embed__grade module-embed__grade--'.e(strtolower($grade)).'">'.e($grade).'</span>'
            : '';

        return '<aside class="module-embed module-embed--hadith" dir="rtl">'
            .$this->embedLabel('scroll-text', __('articles.embed.hadith'))
            .'<div class="module-embed__body module-embed__arabic"><p>'.e($block['text'] ?? '').'</p></div>'
            .'<div class="module-embed__footer">'
            .$this->embedLink($href, 'external-link', $source !== '' ? $source : __('articles.embed.view_hadith'))
            .$gradeBadge
            .'</div>'
            .'</aside>';
    }

    private function renderSahabiBlock(array $block): string
    {
        $name = (string) ($block['name'] ?? '');
        $kunya = (string) ($block['kunya'] ?? '');
        $href = $this->moduleUrl('public.sahaba.show', $block['slug'] ?? $block['sahabi_id'] ?? null);

        return '<aside class="module-embed module-embed--sahabi" dir="rtl">'
            .$this->embedLabel('user-round', __('articles.embed.companion'))
            .'<div class="module-embed__body"><strong>'.e($name).'</strong>'
            .($kunya !== '' ? ' <span class="module-embed__kunya">('.e($kunya).')</span>' : '')
            .'</div>'
            .$this->embedLink($href, 'arrow-left-circle', __('articles.embed.view_companion'))
            .'</aside>';
    }

    private function renderSeerahBlock(array $block): string
    {
        $title = (string) ($block['title'] ?? '');
        $year = (string) ($block['hijri_year'] ?? '');
        $href = $this->moduleUrl('public.seerah.event', $block['slug'] ?? $block['event_id'] ?? null);

        return '<aside class="module-embed module-embed--seerah" dir="rtl">'
            .$this->embedLabel('map-pin', __('articles.embed.seerah'))
            .'<div class="module-embed__body"><strong>'.e($title).'</strong>'
            .($year !== '' ? ' <span class="module-embed__year">'.e($year).' '.e(__('articles.embed.hijri')).'</span>' : '')
            .'</div>'
            .$this->embedLink($href, 'arrow-left-circle', __('articles.embed.view_event'))
            .'</aside>';
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
