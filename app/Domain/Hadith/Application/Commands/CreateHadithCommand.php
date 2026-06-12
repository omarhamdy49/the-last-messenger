<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Application\Commands;

use App\Domain\Shared\Application\Commands\Command;

/**
 * Command to create a new Hadith
 */
final readonly class CreateHadithCommand implements Command
{
    /**
     * @param  int  $bookId  The book this hadith belongs to
     * @param  string  $hadithNumber  The hadith number within the book
     * @param  array<string, string>  $translations  Translations keyed by language code
     * @param  string|null  $gradingStatus  The grading status (sahih, hasan, daif, etc.)
     * @param  int|null  $chapterId  Optional chapter ID
     * @param  array<int>  $narratorIds  Optional narrator IDs
     * @param  array<int>  $themeIds  Optional theme IDs
     */
    public function __construct(
        public int $bookId,
        public string $hadithNumber,
        public array $translations = [],
        public ?string $gradingStatus = null,
        public ?int $chapterId = null,
        public array $narratorIds = [],
        public array $themeIds = [],
    ) {}
}
