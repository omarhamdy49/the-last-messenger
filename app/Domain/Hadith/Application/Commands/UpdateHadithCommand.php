<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Application\Commands;

use App\Domain\Shared\Application\Commands\Command;

/**
 * Command to update an existing Hadith
 */
final readonly class UpdateHadithCommand implements Command
{
    /**
     * @param int $hadithId The hadith ID to update
     * @param array<string, string>|null $translations Translations to update (keyed by language code)
     * @param string|null $gradingStatus The new grading status
     * @param int|null $chapterId The new chapter ID
     * @param array<int>|null $narratorIds Narrator IDs to set (replaces existing)
     * @param array<int>|null $themeIds Theme IDs to set (replaces existing)
     */
    public function __construct(
        public int $hadithId,
        public ?array $translations = null,
        public ?string $gradingStatus = null,
        public ?int $chapterId = null,
        public ?array $narratorIds = null,
        public ?array $themeIds = null,
    ) {}
}
