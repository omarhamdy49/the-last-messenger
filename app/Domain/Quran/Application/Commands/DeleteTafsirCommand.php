<?php

declare(strict_types=1);

namespace App\Domain\Quran\Application\Commands;

/**
 * Command to delete a tafsir
 */
final readonly class DeleteTafsirCommand
{
    public function __construct(
        public int $id,
    ) {}
}
