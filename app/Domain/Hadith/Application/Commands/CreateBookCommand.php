<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Application\Commands;

use App\Domain\Shared\Application\Commands\Command;

/**
 * Command to create a new Hadith Book
 */
final readonly class CreateBookCommand implements Command
{
    /**
     * @param  int  $collectionId  The collection this book belongs to
     * @param  array<string, string>  $title  Book title translations
     * @param  string  $slug  URL-friendly slug
     * @param  int  $bookNumber  The book number within the collection
     * @param  array<string, string>  $chapterTitle  Chapter title translations
     * @param  int  $hadithCount  Total hadith count in this book
     */
    public function __construct(
        public int $collectionId,
        public array $title,
        public string $slug,
        public int $bookNumber,
        public array $chapterTitle = [],
        public int $hadithCount = 0,
    ) {}
}
