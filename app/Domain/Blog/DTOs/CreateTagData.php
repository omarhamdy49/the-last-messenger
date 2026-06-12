<?php

declare(strict_types=1);

namespace App\Domain\Blog\DTOs;

use App\Http\Requests\Admin\StoreTagRequest;

/**
 * Create Tag DTO
 *
 * Carries the validated, type-safe payload needed to create a new tag.
 * Built from the incoming StoreTagRequest so that service classes never
 * touch the HTTP layer directly.
 *
 * @param  array<string, string|null>  $name  Locale-keyed names, e.g. ['ar' => '...', 'en' => '...'].
 * @param  array<string, string|null>  $description  Locale-keyed descriptions.
 */
final class CreateTagData
{
    public function __construct(
        public readonly array $name,
        public readonly array $description,
        public readonly string $type,
        public readonly ?string $color,
        public readonly ?string $slug,
    ) {}

    /**
     * Construct a CreateTagData instance from the validated store request.
     */
    public static function fromRequest(StoreTagRequest $request): self
    {
        $validated = $request->validated();

        return new self(
            name: $validated['name'] ?? [],
            description: $validated['description'] ?? [],
            type: $validated['type'],
            color: $validated['color'] ?? null,
            slug: $validated['slug'] ?? null,
        );
    }
}
