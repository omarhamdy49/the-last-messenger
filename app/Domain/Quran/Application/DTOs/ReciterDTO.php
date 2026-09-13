<?php

declare(strict_types=1);

namespace App\Domain\Quran\Application\DTOs;

use App\Domain\Quran\Models\Reciter;
use JsonSerializable;

/**
 * Reciter Data Transfer Object
 *
 * Represents Reciter data for API responses.
 */
final readonly class ReciterDTO implements JsonSerializable
{
    /**
     * @param  array<string, string>  $name
     * @param  array<string, string>|null  $description
     */
    public function __construct(
        public int $id,
        public string $slug,
        public array $name,
        public ?array $description,
        public ?string $style,
        public ?string $country,
        public ?string $profileImage,
        public bool $isFeatured,
        public bool $isActive,
        public int $displayOrder,
        public int $recitationsCount,
        public ?string $createdAt,
        public ?string $updatedAt,
        public ?string $verseAudioTemplate = null,
        public string $kind = 'recitation',
    ) {}

    /**
     * Create from Reciter model
     */
    public static function fromModel(Reciter $reciter, string $locale = 'ar'): self
    {
        return new self(
            id: $reciter->id,
            slug: $reciter->slug ?? '',
            // Names live in reciter_translations (HasLocalizedContent), not a JSON column.
            name: $reciter->getTranslations('name') ?: ($reciter->name ?? []),
            description: $reciter->getTranslations('description') ?: $reciter->description,
            style: $reciter->style,
            country: $reciter->country,
            profileImage: $reciter->profile_image_url,
            isFeatured: $reciter->is_featured ?? false,
            isActive: $reciter->is_active ?? true,
            displayOrder: $reciter->display_order ?? 0,
            recitationsCount: (int) ($reciter->recitations_count ?? 0),
            createdAt: $reciter->created_at?->toIso8601String(),
            updatedAt: $reciter->updated_at?->toIso8601String(),
            verseAudioTemplate: $reciter->verseTemplate(),
            kind: $reciter->kind ?? 'recitation',
        );
    }

    /**
     * Get name in locale
     */
    public function getDisplayName(string $locale = 'ar'): string
    {
        return $this->name[$locale] ?? $this->name['ar'] ?? '';
    }

    /**
     * Get description in locale
     */
    public function getDescription(string $locale = 'ar'): ?string
    {
        return $this->description[$locale] ?? $this->description['ar'] ?? null;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'name' => $this->name,
            'description' => $this->description,
            'style' => $this->style,
            'country' => $this->country,
            'profile_image' => $this->profileImage,
            'is_featured' => $this->isFeatured,
            'is_active' => $this->isActive,
            'display_order' => $this->displayOrder,
            'recitations_count' => $this->recitationsCount,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
            'verse_audio_template' => $this->verseAudioTemplate,
            'kind' => $this->kind,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
