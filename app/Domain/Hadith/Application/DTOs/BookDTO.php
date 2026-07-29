<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Application\DTOs;

use App\Domain\Hadith\Models\HadithBook;
use JsonSerializable;

/**
 * Book Data Transfer Object
 *
 * Represents HadithBook data for API responses.
 */
final readonly class BookDTO implements JsonSerializable
{
    public function __construct(
        public int $id,
        public int $collectionId,
        public string $titleAr,
        public ?string $titleEn,
        public string $slugAr,
        public ?string $slugEn,
        public int $bookNumber,
        public ?string $chapterTitleAr,
        public ?string $chapterTitleEn,
        public int $hadithCount,
        public int $chaptersCount,
        public ?string $collectionName,
        public ?string $createdAt,
        public ?string $updatedAt,
    ) {}

    /**
     * Create from HadithBook model
     */
    public static function fromModel(HadithBook $book, string $locale = 'ar'): self
    {
        return new self(
            id: $book->id,
            collectionId: $book->collection_id,
            titleAr: $book->title_ar ?? '',
            titleEn: $book->title_en,
            slugAr: (string) $book->book_number,
            slugEn: (string) $book->book_number,
            bookNumber: (int) ($book->book_number ?? 0),
            chapterTitleAr: $book->description['ar'] ?? null,
            chapterTitleEn: $book->description['en'] ?? null,
            // Repositories alias withCount() as either hadith_count or Laravel's default
            // hadiths_count (kept for API resources) — accept both before the N+1 fallback.
            hadithCount: $book->hadith_count ?? $book->hadiths_count ?? $book->hadiths()->count(),
            chaptersCount: $book->chapters_count ?? $book->chapters()->count(),
            collectionName: $book->collection?->getNameInLocale($locale),
            createdAt: $book->created_at?->toIso8601String(),
            updatedAt: $book->updated_at?->toIso8601String(),
        );
    }

    /**
     * Get title in specified locale
     */
    public function getTitle(string $locale = 'ar'): string
    {
        return $locale === 'ar' ? $this->titleAr : ($this->titleEn ?? $this->titleAr);
    }

    /**
     * Get slug in specified locale
     */
    public function getSlug(string $locale = 'ar'): string
    {
        return $locale === 'ar' ? $this->slugAr : ($this->slugEn ?? $this->slugAr);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'collection_id' => $this->collectionId,
            'title_ar' => $this->titleAr,
            'title_en' => $this->titleEn,
            'slug_ar' => $this->slugAr,
            'slug_en' => $this->slugEn,
            'book_number' => $this->bookNumber,
            'chapter_title_ar' => $this->chapterTitleAr,
            'chapter_title_en' => $this->chapterTitleEn,
            'hadith_count' => $this->hadithCount,
            'chapters_count' => $this->chaptersCount,
            'collection_name' => $this->collectionName,
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
