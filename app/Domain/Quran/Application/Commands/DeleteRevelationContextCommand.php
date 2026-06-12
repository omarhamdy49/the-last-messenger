<?php

declare(strict_types=1);

namespace App\Domain\Quran\Application\Commands;

/**
 * Command to delete a revelation context (Asbab al-Nuzul)
 */
final readonly class DeleteRevelationContextCommand
{
    public function __construct(
        public int $id,
    ) {}
}
