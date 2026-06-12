<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Application\DTOs;

/**
 * Chapter Context Data Transfer Object
 *
 * Header context for the public chapter (kitab) reader page: the resolved
 * collection + book and the adjacent books (by book_number within the same
 * collection) used for previous/next chapter navigation.
 *
 * Note: hadiths are linked to books (hadith_hadiths.book_id) — the deeper
 * hadith_chapters rows carry no hadith linkage in the schema, so the public
 * "chapter" unit is the book (kitab) inside a collection.
 */
final readonly class ChapterContextDTO
{
    public function __construct(
        public CollectionDTO $collection,
        public BookDTO $book,
        public ?int $previousNumber,
        public ?string $previousTitleAr,
        public ?string $previousTitleEn,
        public ?int $nextNumber,
        public ?string $nextTitleAr,
        public ?string $nextTitleEn,
    ) {}

    public function getPreviousTitle(string $locale = 'ar'): ?string
    {
        return $locale === 'ar' ? $this->previousTitleAr : ($this->previousTitleEn ?? $this->previousTitleAr);
    }

    public function getNextTitle(string $locale = 'ar'): ?string
    {
        return $locale === 'ar' ? $this->nextTitleAr : ($this->nextTitleEn ?? $this->nextTitleAr);
    }
}
