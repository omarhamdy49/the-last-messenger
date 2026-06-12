<?php

declare(strict_types=1);

namespace App\Domain\Blog\DTOs;

use App\Http\Requests\Admin\StoreCategoryRequest;

/**
 * Create Category DTO
 *
 * Carries the validated, type-safe payload needed to create a new category.
 * Built from the incoming StoreCategoryRequest so that service classes never
 * touch the HTTP layer directly.
 *
 * @param  array<string, string|null>  $name  Locale-keyed names, e.g. ['ar' => '...', 'en' => '...'].
 * @param  array<string, string|null>  $description  Locale-keyed descriptions.
 */
final class CreateCategoryData
{
    public function __construct(
        public readonly array $name,
        public readonly array $description,
        public readonly string $type,
        public readonly ?string $slug,
        public readonly ?int $parentId,
        public readonly ?string $icon,
        public readonly ?string $color,
        public readonly ?int $displayOrder,
    ) {}

    /**
     * Construct a CreateCategoryData instance from the validated store request.
     */
    public static function fromRequest(StoreCategoryRequest $request): self
    {
        $validated = $request->validated();

        return new self(
            name: $validated['name'] ?? [],
            description: $validated['description'] ?? [],
            type: $validated['type'],
            slug: $validated['slug'] ?? null,
            parentId: isset($validated['parent_id']) ? (int) $validated['parent_id'] : null,
            icon: $validated['icon'] ?? null,
            color: $validated['color'] ?? null,
            displayOrder: isset($validated['display_order']) ? (int) $validated['display_order'] : null,
        );
    }
}
