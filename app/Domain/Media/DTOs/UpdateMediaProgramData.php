<?php

declare(strict_types=1);

namespace App\Domain\Media\DTOs;

use App\Http\Requests\Admin\MediaProgram\UpdateMediaProgramRequest;
use Illuminate\Http\UploadedFile;

/**
 * Typed payload for updating a MediaProgram.
 */
final readonly class UpdateMediaProgramData
{
    public function __construct(
        public string $slug,
        public array $title,
        public array $description,
        public array $host,
        public array $seoTitle,
        public array $seoDescription,
        public array $seoKeywords,
        public string $category,
        public string $maturityRating,
        public ?int $year,
        public int $totalSeasons,
        public int $totalEpisodes,
        public float $rating,
        public bool $isFeatured,
        public bool $isTrending,
        public bool $isPublished,
        public ?string $publishAt,
        public int $displayOrder,
        public ?UploadedFile $poster,
        public ?UploadedFile $banner,
        public ?int $posterId,
        public ?int $bannerId,
    ) {}

    public static function fromRequest(UpdateMediaProgramRequest $request): self
    {
        $validated = $request->validated();

        return new self(
            slug: $validated['slug'],
            title: $validated['title'] ?? [],
            description: $validated['description'] ?? [],
            host: $validated['host'] ?? [],
            seoTitle: $validated['seo_title'] ?? [],
            seoDescription: $validated['seo_description'] ?? [],
            seoKeywords: $validated['seo_keywords'] ?? [],
            category: $validated['category'],
            maturityRating: $validated['maturity_rating'] ?? 'all',
            year: isset($validated['year']) ? (int) $validated['year'] : null,
            totalSeasons: (int) ($validated['total_seasons'] ?? 1),
            totalEpisodes: (int) ($validated['total_episodes'] ?? 0),
            rating: (float) ($validated['rating'] ?? 0),
            isFeatured: $request->boolean('is_featured'),
            isTrending: $request->boolean('is_trending'),
            isPublished: $request->boolean('is_published'),
            publishAt: $validated['publish_at'] ?? null,
            displayOrder: (int) ($validated['display_order'] ?? 0),
            poster: $request->hasFile('poster') ? $request->file('poster') : null,
            banner: $request->hasFile('banner') ? $request->file('banner') : null,
            posterId: isset($validated['poster_id']) ? (int) $validated['poster_id'] : null,
            bannerId: isset($validated['banner_id']) ? (int) $validated['banner_id'] : null,
        );
    }
}
