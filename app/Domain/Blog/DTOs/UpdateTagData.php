<?php

declare(strict_types=1);

namespace App\Domain\Blog\DTOs;

use App\Http\Requests\Admin\UpdateTagRequest;

/**
 * Update Tag DTO
 *
 * Carries the validated, type-safe payload needed to update an existing tag.
 * Built from the incoming UpdateTagRequest so that service classes never
 * touch the HTTP layer directly.
 *
 * @param  array<string, string|null>  $name  Locale-keyed names, e.g. ['ar' => '...', 'en' => '...'].
 */
final class UpdateTagData
{
    public function __construct(
        public readonly array $name,
        public readonly string $type,
        public readonly ?string $slug,
    ) {}

    /**
     * Construct an UpdateTagData instance from the validated update request.
     */
    public static function fromRequest(UpdateTagRequest $request): self
    {
        $validated = $request->validated();

        return new self(
            name: $validated['name'] ?? [],
            type: $validated['type'],
            slug: $validated['slug'] ?? null,
        );
    }
}
