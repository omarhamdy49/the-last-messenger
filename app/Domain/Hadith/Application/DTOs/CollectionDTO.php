<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Application\DTOs;

use App\Domain\Hadith\Models\HadithCollection;
use JsonSerializable;

/**
 * Collection Data Transfer Object
 *
 * Represents HadithCollection data for API responses.
 */
final readonly class CollectionDTO implements JsonSerializable
{
    public function __construct(
        public int $id,
        public string $nameAr,
        public ?string $nameEn,
        public string $slug,
        public ?string $descriptionAr,
        public ?string $descriptionEn,
        public ?int $authorId,
        public ?string $authorName,
        public int $hadithCount,
        public int $booksCount,
        public bool $isActive,
        public ?string $createdAt,
        public ?string $updatedAt,
    ) {}

    /**
     * Create from HadithCollection model
     */
    public static function fromModel(HadithCollection $collection, string $locale = 'ar'): self
    {
        // Real schema: slug_ar/slug_en (no generic `slug`) and total_hadiths (no `hadith_count`).
        $slug = $locale === 'ar'
            ? ($collection->slug_ar ?? $collection->slug_en ?? '')
            : ($collection->slug_en ?? $collection->slug_ar ?? '');

        return new self(
            id: $collection->id,
            nameAr: $collection->name_ar ?? '',
            nameEn: $collection->name_en,
            slug: $slug,
            descriptionAr: $collection->description_ar,
            descriptionEn: $collection->description_en,
            authorId: null,
            authorName: null,
            hadithCount: (int) ($collection->total_hadiths ?? 0),
            booksCount: (int) ($collection->books_count ?? $collection->books()->count()),
            // The hadith_collections table has no is_active column; collections are always active.
            isActive: true,
            createdAt: $collection->created_at?->toIso8601String(),
            updatedAt: $collection->updated_at?->toIso8601String(),
        );
    }

    /**
     * Get name in specified locale
     */
    public function getName(string $locale = 'ar'): string
    {
        return $locale === 'ar' ? $this->nameAr : ($this->nameEn ?? $this->nameAr);
    }

    /**
     * Get description in specified locale
     */
    public function getDescription(string $locale = 'ar'): ?string
    {
        return $locale === 'ar' ? $this->descriptionAr : ($this->descriptionEn ?? $this->descriptionAr);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name_ar' => $this->nameAr,
            'name_en' => $this->nameEn,
            'slug' => $this->slug,
            'description_ar' => $this->descriptionAr,
            'description_en' => $this->descriptionEn,
            'author_id' => $this->authorId,
            'author_name' => $this->authorName,
            'hadith_count' => $this->hadithCount,
            'books_count' => $this->booksCount,
            'is_active' => $this->isActive,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
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
