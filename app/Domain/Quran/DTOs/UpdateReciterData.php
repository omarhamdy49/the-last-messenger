<?php

declare(strict_types=1);

namespace App\Domain\Quran\DTOs;

use App\Http\Requests\Admin\UpdateReciterRequest;
use Illuminate\Http\UploadedFile;

/**
 * Update Reciter DTO
 *
 * Carries the validated, type-safe payload needed to update an existing Quran reciter.
 * Built from the incoming UpdateReciterRequest so that service classes never
 * touch the HTTP layer directly.
 *
 * @param  array<string, string|null>  $name  Locale-keyed names, e.g. ['ar' => '...', 'en' => '...'].
 * @param  array<string, string|null>  $description  Locale-keyed descriptions.
 */
final class UpdateReciterData
{
    public function __construct(
        public readonly array $name,
        public readonly array $description,
        public readonly ?string $slug,
        public readonly ?string $country,
        public readonly ?string $style,
        public readonly ?UploadedFile $profileImage,
        public readonly ?int $profileImageId,
        public readonly bool $isFeatured,
        public readonly ?int $displayOrder,
    ) {}

    /**
     * Construct an UpdateReciterData instance from the validated update request.
     */
    public static function fromRequest(UpdateReciterRequest $request): self
    {
        $validated = $request->validated();

        return new self(
            name: $validated['name'] ?? [],
            description: $validated['description'] ?? [],
            slug: $validated['slug'] ?? null,
            country: $validated['country'] ?? null,
            style: $validated['style'] ?? null,
            profileImage: $request->hasFile('profile_image') ? $request->file('profile_image') : null,
            profileImageId: (int) ($request->input('profile_image_id') ?: 0) ?: null,
            isFeatured: $request->boolean('is_featured'),
            displayOrder: isset($validated['display_order']) ? (int) $validated['display_order'] : null,
        );
    }
}
