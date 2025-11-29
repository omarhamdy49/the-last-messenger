<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Application\Commands;

use App\Domain\Shared\Application\Commands\Command;

/**
 * Command to update an existing Hadith Book
 */
final readonly class UpdateBookCommand implements Command
{
    /**
     * @param int $bookId The book ID to update
     * @param array<string, string>|null $title Book title translations
     * @param string|null $slug URL-friendly slug
     * @param int|null $bookNumber The book number within the collection
     * @param array<string, string>|null $chapterTitle Chapter title translations
     * @param int|null $hadithCount Total hadith count in this book
     */
    public function __construct(
        public int $bookId,
        public ?array $title = null,
        public ?string $slug = null,
        public ?int $bookNumber = null,
        public ?array $chapterTitle = null,
        public ?int $hadithCount = null,
    ) {}
}
