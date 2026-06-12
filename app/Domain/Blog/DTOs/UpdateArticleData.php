<?php

declare(strict_types=1);

namespace App\Domain\Blog\DTOs;

use App\Http\Requests\Admin\Article\UpdateArticleRequest;
use App\Models\Article;
use Illuminate\Http\UploadedFile;

/**
 * Update Article DTO
 *
 * Carries the validated, type-safe payload needed by UpdateArticleAction.
 * Falls back to existing article values when a field is not present in the request.
 */
final class UpdateArticleData
{
    /**
     * @param  array<string, string>  $titles
     * @param  array<string, string>  $slugs
     * @param  array<string, string|null>  $descriptions
     * @param  array<string, string|null>  $excerpts
     * @param  array<string, string|array|null>  $contents
     * @param  array<string, array|null>  $tocs
     * @param  array<string, string|null>  $summaries
     * @param  array<string, string|null>  $seoTitles
     * @param  array<string, string|null>  $seoDescriptions
     * @param  array<string, bool>  $isReady
     * @param  array<int>|null  $tags
     * @param  array<int>|null  $mediaItems
     * @param  array<int>|null  $additionalCategories
     */
    public function __construct(
        public readonly ?UploadedFile $thumb,
        public readonly int $writerId,
        public readonly int $readTime,
        public readonly bool $isFeatured,
        public readonly bool $isPinned,
        public readonly ?int $categoryId,
        public readonly ?string $hijriDate,
        public readonly string $status,
        public readonly ?string $scheduledAt,
        public readonly ?array $statistics,
        public readonly ?array $references,
        public readonly array $titles,
        public readonly array $slugs,
        public readonly array $descriptions,
        public readonly array $excerpts,
        public readonly array $contents,
        public readonly array $tocs,
        public readonly array $summaries,
        public readonly array $seoTitles,
        public readonly array $seoDescriptions,
        public readonly array $isReady,
        public readonly ?array $tags,
        public readonly ?array $mediaItems,
        public readonly ?array $additionalCategories,
        public readonly ?array $series = null,
    ) {}

    /**
     * Construct an UpdateArticleData instance from the validated update request
     * and the current article state (for fallback values).
     */
    public static function fromRequest(UpdateArticleRequest $request, Article $article): self
    {
        $validated = $request->validated();
        $resolvedStatus = $validated['status'] ?? $article->status ?? 'draft';

        return new self(
            thumb: $request->hasFile('thumb') ? $request->file('thumb') : null,
            writerId: (int) ($validated['writer_id'] ?? $article->writer_id),
            readTime: (int) ($validated['read_time'] ?? $article->read_time),
            isFeatured: $request->boolean('is_featured'),
            isPinned: $request->boolean('is_pinned'),
            categoryId: isset($validated['category_id']) ? (int) $validated['category_id'] : null,
            hijriDate: $validated['hijri_date'] ?? null,
            status: $resolvedStatus,
            scheduledAt: $resolvedStatus === 'scheduled'
                                      ? ($validated['scheduled_at'] ?? $article->scheduled_at)
                                      : null,
            statistics: $validated['statistics'] ?? $article->statistics,
            references: $validated['references'] ?? $article->references,
            titles: $validated['title'] ?? [],
            slugs: $validated['slug'] ?? [],
            descriptions: $validated['description'] ?? [],
            excerpts: $validated['excerpt'] ?? [],
            contents: $validated['content'] ?? [],
            tocs: $validated['toc'] ?? [],
            summaries: $validated['summary'] ?? [],
            seoTitles: $validated['seo_title'] ?? [],
            seoDescriptions: $validated['seo_description'] ?? [],
            isReady: $validated['is_ready'] ?? [],
            tags: $validated['tags'] ?? null,
            mediaItems: isset($validated['media_items']) ? $validated['media_items'] : null,
            additionalCategories: $validated['additional_categories'] ?? null,
            series: $validated['series'] ?? null,
        );
    }
}
