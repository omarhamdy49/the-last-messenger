<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Application\Commands;

use App\Domain\Shared\Application\Commands\Command;

/**
 * Command to delete a Hadith
 */
final readonly class DeleteHadithCommand implements Command
{
    /**
     * @param int $hadithId The hadith ID to delete
     * @param bool $forceDelete Whether to permanently delete (true) or soft delete (false)
     */
    public function __construct(
        public int $hadithId,
        public bool $forceDelete = false,
    ) {}
}
