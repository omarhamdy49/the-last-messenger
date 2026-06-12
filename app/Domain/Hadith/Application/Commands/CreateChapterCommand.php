<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Application\Commands;

use App\Domain\Shared\Application\Commands\Command;

/**
 * Command to create a new Hadith Chapter
 */
final readonly class CreateChapterCommand implements Command
{
    /**
     * @param  int  $bookId  The book this chapter belongs to
     * @param  array<string, string>  $title  Chapter title translations
     * @param  int  $chapterNumber  The chapter number within the book
     * @param  array<string, string>  $intro  Optional introduction translations
     */
    public function __construct(
        public int $bookId,
        public array $title,
        public int $chapterNumber,
        public array $intro = [],
    ) {}
}
