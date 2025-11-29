<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Application\Commands;

use App\Domain\Shared\Application\Commands\Command;

/**
 * Command to update hadith grading
 */
final readonly class UpdateGradingCommand implements Command
{
    /**
     * @param int $hadithId The hadith ID
     * @param string $gradingStatus New grading status (sahih, hasan, daif, etc.)
     * @param int|null $scholarId Scholar who provided the grading
     * @param int|null $gradingLevelId The grading level ID
     * @param string|null $notes Additional notes about the grading
     */
    public function __construct(
        public int $hadithId,
        public string $gradingStatus,
        public ?int $scholarId = null,
        public ?int $gradingLevelId = null,
        public ?string $notes = null,
    ) {}
}
