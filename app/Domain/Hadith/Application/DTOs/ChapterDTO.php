<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Application\DTOs;

use App\Domain\Hadith\Models\HadithChapter;
use JsonSerializable;

/**
 * Chapter Data Transfer Object
 *
 * Represents HadithChapter data for API responses.
 */
final readonly class ChapterDTO implements JsonSerializable
{
    public function __construct(
        public int $id,
        public int $bookId,
        public string $titleAr,
        public ?string $titleEn,
        public int $chapterNumber,
        public ?string $introAr,
        public ?string $introEn,
        public int $hadithsCount,
        public ?string $bookTitle,
        public ?string $createdAt,
        public ?string $updatedAt,
    ) {}

    /**
     * Create from HadithChapter model
     */
    public static function fromModel(HadithChapter $chapter, string $locale = 'ar'): self
    {
        return new self(
            id: $chapter->id,
            bookId: $chapter->book_id,
            titleAr: $chapter->name['ar'] ?? '',
            titleEn: $chapter->name['en'] ?? null,
            chapterNumber: (int) $chapter->chapter_number,
            introAr: $chapter->description['ar'] ?? null,
            introEn: $chapter->description['en'] ?? null,
            hadithsCount: $chapter->hadiths_count ?? $chapter->hadiths()->count(),
            bookTitle: $chapter->book?->getTitleInLocale($locale),
            createdAt: $chapter->created_at?->toIso8601String(),
            updatedAt: $chapter->updated_at?->toIso8601String(),
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
     * Get intro in specified locale
     */
    public function getIntro(string $locale = 'ar'): ?string
    {
        return $locale === 'ar' ? $this->introAr : ($this->introEn ?? $this->introAr);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'book_id' => $this->bookId,
            'title_ar' => $this->titleAr,
            'title_en' => $this->titleEn,
            'chapter_number' => $this->chapterNumber,
            'intro_ar' => $this->introAr,
            'intro_en' => $this->introEn,
            'hadiths_count' => $this->hadithsCount,
            'book_title' => $this->bookTitle,
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
