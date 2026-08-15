<?php

declare(strict_types=1);

namespace App\Domain\Blog\DTOs;

use App\Http\Requests\Admin\StoreSeriesRequest;
use Illuminate\Http\UploadedFile;

/**
 * Create Series DTO
 *
 * Carries the validated, type-safe payload needed to create a new series.
 * Built from the incoming StoreSeriesRequest so that service classes never
 * touch the HTTP layer directly.
 */
final class CreateSeriesData
{
    /**
     * @param  array<string, string|null>  $title  Locale-keyed titles, e.g. ['ar' => '...', 'en' => '...'].
     * @param  array<string, string|null>  $description  Locale-keyed descriptions.
     * @param  array<int>|null  $articleIds  Article IDs to attach with positions.
     */
    public function __construct(
        public readonly array $title,
        public readonly array $description,
        public readonly ?string $slug,
        public readonly ?UploadedFile $thumbnail,
        public readonly ?int $thumbnailId,
        public readonly ?array $articleIds,
    ) {}

    /**
     * Construct a CreateSeriesData instance from the validated store request.
     */
    public static function fromRequest(StoreSeriesRequest $request): self
    {
        $validated = $request->validated();

        return new self(
            title: $validated['title'] ?? [],
            description: $validated['description'] ?? [],
            slug: $validated['slug'] ?? null,
            thumbnail: $request->hasFile('thumbnail') ? $request->file('thumbnail') : null,
            thumbnailId: isset($validated['thumbnail_id']) ? (int) $validated['thumbnail_id'] : null,
            articleIds: $validated['article_ids'] ?? null,
        );
    }
}
