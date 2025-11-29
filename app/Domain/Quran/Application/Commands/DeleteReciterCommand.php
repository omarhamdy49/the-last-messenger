<?php

declare(strict_types=1);

namespace App\Domain\Quran\Application\Commands;

/**
 * Command to delete a Quran reciter
 */
final readonly class DeleteReciterCommand
{
    public function __construct(
        public int $id,
    ) {}
}
