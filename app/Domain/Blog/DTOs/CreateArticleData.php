<?php

declare(strict_types=1);

namespace App\Domain\Blog\DTOs;

use App\Http\Requests\Admin\Article\StoreArticleRequest;
use Illuminate\Http\UploadedFile;

/**
 * Create Article DTO
 *
 * Carries the validated, type-safe payload needed by CreateArticleAction.
 * Built from the incoming StoreArticleRequest so that action classes never
 * touch the HTTP layer directly.
 */
final class CreateArticleData
{
    /**
     * @param  array<string, string>  $titles  Locale-keyed titles, e.g. ['ar' => '...', 'en' => '...'].
     * @param  array<string, string>  $slugs  Locale-keyed slugs (may be empty; action will generate).
     * @param  array<string, string|null>  $descriptions  Locale-keyed descriptions.
     * @param  array<string, string|null>  $excerpts  Locale-keyed excerpts.
     * @param  array<string, string|array|null>  $contents  Locale-keyed body content (HTML string or blocks array).
     * @param  array<string, array|null>  $tocs  Locale-keyed TOC arrays (may be empty; action will generate).
     * @param  array<string, string|null>  $summaries  Locale-keyed summaries.
     * @param  array<string, string|null>  $seoTitles  Locale-keyed SEO titles.
     * @param  array<string, string|null>  $seoDescriptions  Locale-keyed SEO descriptions.
     * @param  array<string, bool>  $isReady  Locale-keyed readiness flags.
     * @param  array<int>|null  $tags  Tag IDs to attach.
     * @param  array<int>|null  $mediaItems  Media IDs to sync.
     * @param  array<int>|null  $episodes  Media episode IDs for the video queue, in order.
     * @param  array<int>|null  $additionalCategories  Additional category IDs to attach.
     * @param  array<int>|null  $series  Series IDs to attach (with positions).
     * @param  array<int, array{media_id: int, file: UploadedFile}>  $galleryFiles
     *                                                                              Gallery images, already materialised from the media library by the
     *                                                                              HTTP layer, paired with the media id they came from.
     */
    public function __construct(
        public readonly ?UploadedFile $thumb,
        public readonly int $writerId,
        public readonly ?int $readTime,
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
        public readonly ?array $episodes,
        public readonly ?array $additionalCategories,
        public readonly ?array $series = null,
        public readonly array $galleryFiles = [],
    ) {}

    /**
     * Construct a CreateArticleData instance from the validated store request.
     *
     * @param  array<int, array{media_id: int, file: UploadedFile}>  $galleryFiles
     */
    public static function fromRequest(
        StoreArticleRequest $request,
        ?UploadedFile $thumbOverride = null,
        array $galleryFiles = [],
    ): self {
        $validated = $request->validated();

        return new self(
            thumb: $request->hasFile('thumb') ? $request->file('thumb') : $thumbOverride,
            writerId: (int) ($validated['writer_id'] ?? auth()->id()),
            readTime: isset($validated['read_time']) ? (int) $validated['read_time'] : null,
            isFeatured: $request->boolean('is_featured'),
            isPinned: $request->boolean('is_pinned'),
            categoryId: isset($validated['category_id']) ? (int) $validated['category_id'] : null,
            hijriDate: $validated['hijri_date'] ?? null,
            status: $validated['status'] ?? 'draft',
            scheduledAt: ($validated['status'] ?? 'draft') === 'scheduled'
                                      ? ($validated['scheduled_at'] ?? null)
                                      : null,
            statistics: $validated['statistics'] ?? null,
            references: $validated['references'] ?? null,
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
            mediaItems: $validated['media_items'] ?? null,
            episodes: $validated['episodes'] ?? null,
            additionalCategories: $validated['additional_categories'] ?? null,
            series: $validated['series'] ?? null,
            galleryFiles: $galleryFiles,
        );
    }
}
