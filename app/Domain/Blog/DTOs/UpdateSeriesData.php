<?php

declare(strict_types=1);

namespace App\Domain\Blog\DTOs;

use App\Http\Requests\Admin\UpdateSeriesRequest;
use Illuminate\Http\UploadedFile;

/**
 * Update Series DTO
 *
 * Carries the validated, type-safe payload needed to update an existing series.
 * Built from the incoming UpdateSeriesRequest so that service classes never
 * touch the HTTP layer directly.
 */
final class UpdateSeriesData
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
        public readonly ?array $articleIds,
    ) {}

    /**
     * Construct an UpdateSeriesData instance from the validated update request.
     */
    public static function fromRequest(UpdateSeriesRequest $request): self
    {
        $validated = $request->validated();

        return new self(
            title: $validated['title'] ?? [],
            description: $validated['description'] ?? [],
            slug: $validated['slug'] ?? null,
            thumbnail: $request->hasFile('thumbnail') ? $request->file('thumbnail') : null,
            articleIds: $validated['article_ids'] ?? null,
        );
    }
}
