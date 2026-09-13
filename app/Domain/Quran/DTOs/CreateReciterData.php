<?php

declare(strict_types=1);

namespace App\Domain\Quran\DTOs;

use App\Http\Requests\Admin\StoreReciterRequest;
use Illuminate\Http\UploadedFile;

/**
 * Create Reciter DTO
 *
 * Carries the validated, type-safe payload needed to create a new Quran reciter.
 * Built from the incoming StoreReciterRequest so that service classes never
 * touch the HTTP layer directly.
 *
 * @param  array<string, string|null>  $name  Locale-keyed names, e.g. ['ar' => '...', 'en' => '...'].
 * @param  array<string, string|null>  $description  Locale-keyed descriptions.
 */
final class CreateReciterData
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
        public readonly int $displayOrder,
        public readonly ?int $profileId = null,
        public readonly ?string $riwayah = null,
        public readonly ?string $profileImageUrl = null,
        public readonly bool $removeProfileImage = false,
    ) {}

    /**
     * Construct a CreateReciterData instance from the validated store request.
     */
    public static function fromRequest(StoreReciterRequest $request): self
    {
        $validated = $request->validated();

        return new self(
            name: $validated['name'] ?? [],
            description: $validated['description'] ?? [],
            slug: $validated['slug'] ?? null,
            country: $validated['country'] ?? null,
            style: $validated['style'] ?? null,
            profileId: isset($validated['profile_id']) ? (int) $validated['profile_id'] : null,
            riwayah: $validated['riwayah'] ?? null,
            profileImage: $request->hasFile('profile_image') ? $request->file('profile_image') : null,
            profileImageId: (int) ($request->input('profile_image_id') ?: 0) ?: null,
            profileImageUrl: ($validated['profile_image_url'] ?? null) ?: null,
            removeProfileImage: $request->boolean('remove_profile_image'),
            isFeatured: $request->boolean('is_featured'),
            displayOrder: (int) ($validated['display_order'] ?? 0),
        );
    }
}
